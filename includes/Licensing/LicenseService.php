<?php
/**
 * License Domain Service.
 *
 * @package WPCalibrate\LicenseManager\Licensing
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Licensing;

use WPCalibrate\LicenseManager\Database\Repositories\LicenseRepository;
use WPCalibrate\LicenseManager\Database\Repositories\ProductRepository;
use WPCalibrate\LicenseManager\Domain\License;
use WPCalibrate\LicenseManager\Logging\AuditLogger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LicenseService
 */
final class LicenseService {

	private LicenseRepository $license_repo;
	private ProductRepository $product_repo;
	private AuditLogger $logger;

	public function __construct(
		LicenseRepository $license_repo,
		ProductRepository $product_repo,
		AuditLogger $logger
	) {
		$this->license_repo = $license_repo;
		$this->product_repo = $product_repo;
		$this->logger       = $logger;
	}

	/**
	 * Create a new license.
	 *
	 * @param array $params Creation parameters.
	 * @return array{license: License, plaintext_key: string}
	 */
	public function create_license( array $params ): array {
		$key_bundle = KeyGenerator::generate();

		$product_id = (int) ( $params['product_id'] ?? 0 );
		$is_lifetime = ! empty( $params['is_lifetime'] );

		$expires_at = null;
		if ( ! $is_lifetime ) {
			if ( ! empty( $params['expires_at'] ) ) {
				$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( (string) $params['expires_at'] ) );
			} else {
				$settings     = get_option( 'wpcalibrate_lm_settings', array() );
				$default_days = max( 1, (int) ( $settings['default_expiry_days'] ?? 365 ) );
				$expires_at   = gmdate( 'Y-m-d H:i:s', time() + ( $default_days * DAY_IN_SECONDS ) );
			}
		}

		$limit = isset( $params['activation_limit'] )
			? (int) $params['activation_limit']
			: (int) ( get_option( 'wpcalibrate_lm_settings', array() )['default_activation_limit'] ?? 1 );

		$license = new License(
			array(
				'license_key_hash'      => $key_bundle['hash'],
				'license_key_masked'    => $key_bundle['masked'],
				'license_key_encrypted' => $key_bundle['encrypted'],
				'product_id'            => $product_id,
				'customer_id'          => ! empty( $params['customer_id'] ) ? (int) $params['customer_id'] : null,
				'customer_email'        => sanitize_email( (string) ( $params['customer_email'] ?? '' ) ),
				'order_id'              => ! empty( $params['order_id'] ) ? (int) $params['order_id'] : null,
				'subscription_id'       => ! empty( $params['subscription_id'] ) ? (int) $params['subscription_id'] : null,
				'activation_limit'      => $limit,
				'activation_count'      => 0,
				'is_lifetime'           => $is_lifetime,
				'expires_at'            => $expires_at,
				'status'                => sanitize_text_field( (string) ( $params['status'] ?? 'active' ) ),
				'admin_notes'           => isset( $params['admin_notes'] ) ? sanitize_textarea_field( (string) $params['admin_notes'] ) : null,
				'metadata'              => ! empty( $params['metadata'] ) ? wp_json_encode( $params['metadata'] ) : null,
			)
		);

		$this->license_repo->insert( $license );

		$this->logger->log(
			'license_created',
			$license->id,
			$product_id,
			'',
			array(
				'masked_key'       => $license->license_key_masked,
				'activation_limit' => $license->activation_limit,
				'is_lifetime'      => $license->is_lifetime,
				'expires_at'       => $license->expires_at,
			)
		);

		return array(
			'license'       => $license,
			'plaintext_key' => $key_bundle['plaintext'],
		);
	}

	/**
	 * Find license by plaintext key.
	 *
	 * @param string $raw_key Plaintext key.
	 * @return License|null
	 */
	public function find_by_key( string $raw_key ): ?License {
		$hash = KeyGenerator::hash_key( $raw_key );
		return $this->license_repo->find_by_hash( $hash );
	}

	/**
	 * Change license status.
	 *
	 * @param int    $license_id License ID.
	 * @param string $new_status 'active', 'expired', 'suspended', 'revoked', 'disabled'.
	 * @param string $reason     Administrative reason.
	 * @return bool
	 */
	public function change_status( int $license_id, string $new_status, string $reason = '' ): bool {
		$license = $this->license_repo->find_by_id( $license_id );
		if ( ! $license ) {
			return false;
		}

		$old_status      = $license->status;
		$license->status = $new_status;
		$updated         = $this->license_repo->update_status( $license_id, $new_status );

		if ( $updated ) {
			$this->logger->log(
				'status_changed',
				$license_id,
				$license->product_id,
				'',
				array(
					'old_status' => $old_status,
					'new_status' => $new_status,
					'reason'     => $reason,
				)
			);
		}

		return $updated;
	}

	/**
	 * Extend expiry date by days.
	 *
	 * @param int $license_id
	 * @param int $days
	 * @return bool
	 */
	public function extend_expiry( int $license_id, int $days ): bool {
		$license = $this->license_repo->find_by_id( $license_id );
		if ( ! $license || $license->is_lifetime ) {
			return false;
		}

		$current_time = time();
		$base_time    = ! empty( $license->expires_at ) && strtotime( $license->expires_at ) > $current_time
			? strtotime( $license->expires_at )
			: $current_time;

		$license->expires_at = gmdate( 'Y-m-d H:i:s', $base_time + ( $days * DAY_IN_SECONDS ) );
		if ( 'expired' === $license->status ) {
			$license->status = 'active';
		}

		$updated = $this->license_repo->update( $license );
		if ( $updated ) {
			$this->logger->log(
				'expiry_extended',
				$license_id,
				$license->product_id,
				'',
				array(
					'days_added'    => $days,
					'new_expires_at' => $license->expires_at,
				)
			);
		}

		return $updated;
	}

	/**
	 * Process and mark expired licenses.
	 *
	 * @return int Number of licenses updated.
	 */
	public function process_expired_licenses(): int {
		global $wpdb;
		$table = $this->license_repo->get_table_name();

		$now   = gmdate( 'Y-m-d H:i:s' );
		$query = "UPDATE {$table} SET status = 'expired', updated_at = %s WHERE status = 'active' AND is_lifetime = 0 AND expires_at < %s";

		$count = $wpdb->query( $wpdb->prepare( $query, $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return false === $count ? 0 : (int) $count;
	}
}
