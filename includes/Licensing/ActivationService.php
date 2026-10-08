<?php
/**
 * Site Activation & Deactivation Service.
 *
 * @package WPCalibrate\LicenseManager\Licensing
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Licensing;

use WPCalibrate\LicenseManager\Database\Repositories\ActivationRepository;
use WPCalibrate\LicenseManager\Database\Repositories\LicenseRepository;
use WPCalibrate\LicenseManager\Database\Repositories\ProductRepository;
use WPCalibrate\LicenseManager\Domain\Activation;
use WPCalibrate\LicenseManager\Logging\AuditLogger;
use WPCalibrate\LicenseManager\Support\Environment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ActivationService
 */
final class ActivationService {

	private ActivationRepository $activation_repo;
	private LicenseRepository $license_repo;
	private ProductRepository $product_repo;
	private AuditLogger $logger;

	public function __construct(
		ActivationRepository $activation_repo,
		LicenseRepository $license_repo,
		ProductRepository $product_repo,
		AuditLogger $logger
	) {
		$this->activation_repo = $activation_repo;
		$this->license_repo    = $license_repo;
		$this->product_repo    = $product_repo;
		$this->logger          = $logger;
	}

	/**
	 * Activate a license for a site.
	 *
	 * @param string $raw_key
	 * @param int    $product_id
	 * @param array  $site_data
	 * @return array
	 */
	public function activate( string $raw_key, int $product_id, array $site_data ): array {
		$hash    = KeyGenerator::hash_key( $raw_key );
		$license = $this->license_repo->find_by_hash( $hash );

		if ( ! $license ) {
			return array(
				'success' => false,
				'code'    => 'invalid_key',
				'message' => __( 'The provided license key was not found.', 'wpcalibrate-license-manager' ),
			);
		}

		if ( $license->product_id !== $product_id ) {
			return array(
				'success' => false,
				'code'    => 'product_mismatch',
				'message' => __( 'This license key does not belong to the requested product.', 'wpcalibrate-license-manager' ),
			);
		}

		$product = $this->product_repo->find_by_id( $product_id );
		if ( ! $product ) {
			return array(
				'success' => false,
				'code'    => 'product_not_found',
				'message' => __( 'The target product could not be found.', 'wpcalibrate-license-manager' ),
			);
		}

		// Check status.
		if ( 'revoked' === $license->status ) {
			return array(
				'success' => false,
				'code'    => 'revoked',
				'message' => __( 'This license has been revoked.', 'wpcalibrate-license-manager' ),
			);
		}

		if ( 'suspended' === $license->status ) {
			return array(
				'success' => false,
				'code'    => 'suspended',
				'message' => __( 'This license is currently suspended.', 'wpcalibrate-license-manager' ),
			);
		}

		if ( 'disabled' === $license->status ) {
			return array(
				'success' => false,
				'code'    => 'disabled',
				'message' => __( 'This license is disabled.', 'wpcalibrate-license-manager' ),
			);
		}

		if ( $license->is_expired() ) {
			return array(
				'success' => false,
				'code'    => 'expired',
				'message' => __( 'This license has expired. Please renew to activate on new sites.', 'wpcalibrate-license-manager' ),
			);
		}

		// Normalize site data.
		$site_url        = esc_url_raw( (string) ( $site_data['site_url'] ?? '' ) );
		$site_identifier = sanitize_text_field( (string) ( $site_data['site_identifier'] ?? '' ) );
		if ( empty( $site_identifier ) ) {
			$site_identifier = Environment::hash_site_identifier( $site_url );
		}

		$site_domain = Environment::normalize_domain( $site_url );
		$is_staging  = Environment::is_staging_domain( $site_domain );

		// Check if already activated (Idempotency).
		$existing = $this->activation_repo->find_by_license_and_site( (int) $license->id, $site_identifier );
		if ( $existing ) {
			$existing->site_url          = $site_url;
			$existing->site_domain       = $site_domain;
			$existing->wp_version        = sanitize_text_field( (string) ( $site_data['wp_version'] ?? $existing->wp_version ) );
			$existing->plugin_version    = sanitize_text_field( (string) ( $site_data['plugin_version'] ?? $existing->plugin_version ) );
			$existing->last_validated_at = current_time( 'mysql', true );
			$this->activation_repo->update( $existing );

			return array(
				'success'          => true,
				'code'             => 'already_activated',
				'message'          => __( 'Installation is already activated.', 'wpcalibrate-license-manager' ),
				'license_status'   => $license->status,
				'is_lifetime'      => $license->is_lifetime,
				'expires_at'       => $license->expires_at,
				'activation_limit' => $license->activation_limit,
				'activation_count' => $license->activation_count,
				'is_staging'       => $is_staging,
				'product_name'     => $product->name,
			);
		}

		// If new activation and not staging, verify seat limits.
		if ( ! $is_staging && ! $license->has_available_seats() ) {
			return array(
				'success' => false,
				'code'    => 'activation_limit_reached',
				'message' => sprintf(
					/* translators: %d: Maximum activation seats allowed */
					__( 'Activation limit of %d seat(s) reached. Please upgrade or deactivate an existing site.', 'wpcalibrate-license-manager' ),
					$license->activation_limit
				),
			);
		}

		// Create activation record.
		$activation = new Activation(
			array(
				'license_id'        => $license->id,
				'product_id'        => $product->id,
				'site_identifier'   => $site_identifier,
				'site_url'          => $site_url,
				'site_domain'       => $site_domain,
				'site_ip'           => sanitize_text_field( (string) ( $site_data['site_ip'] ?? '' ) ),
				'wp_version'        => sanitize_text_field( (string) ( $site_data['wp_version'] ?? '' ) ),
				'plugin_version'    => sanitize_text_field( (string) ( $site_data['plugin_version'] ?? '' ) ),
				'is_staging'        => $is_staging,
				'multisite'         => ! empty( $site_data['multisite'] ),
				'last_validated_at' => current_time( 'mysql', true ),
			)
		);

		$this->activation_repo->insert( $activation );
		$this->license_repo->recalculate_activation_count( (int) $license->id );

		// Refresh license state.
		$license = $this->license_repo->find_by_id( (int) $license->id );

		$this->logger->log(
			'site_activated',
			$license->id,
			$product->id,
			$site_domain,
			array(
				'site_identifier' => $site_identifier,
				'is_staging'      => $is_staging,
				'seats_used'      => $license->activation_count,
				'limit'           => $license->activation_limit,
			)
		);

		return array(
			'success'          => true,
			'code'             => 'activated',
			'message'          => __( 'License successfully activated.', 'wpcalibrate-license-manager' ),
			'license_status'   => $license->status,
			'is_lifetime'      => $license->is_lifetime,
			'expires_at'       => $license->expires_at,
			'activation_limit' => $license->activation_limit,
			'activation_count' => $license->activation_count,
			'is_staging'       => $is_staging,
			'product_name'     => $product->name,
		);
	}

	/**
	 * Deactivate a license for a site.
	 *
	 * @param string $raw_key
	 * @param int    $product_id
	 * @param string $site_identifier
	 * @return array
	 */
	public function deactivate( string $raw_key, int $product_id, string $site_identifier ): array {
		$hash    = KeyGenerator::hash_key( $raw_key );
		$license = $this->license_repo->find_by_hash( $hash );

		if ( ! $license ) {
			return array(
				'success' => false,
				'code'    => 'invalid_key',
				'message' => __( 'License key not found.', 'wpcalibrate-license-manager' ),
			);
		}

		if ( $license->product_id !== $product_id ) {
			return array(
				'success' => false,
				'code'    => 'product_mismatch',
				'message' => __( 'Product mismatch.', 'wpcalibrate-license-manager' ),
			);
		}

		$existing = $this->activation_repo->find_by_license_and_site( (int) $license->id, $site_identifier );
		if ( ! $existing ) {
			return array(
				'success' => true,
				'code'    => 'already_deactivated',
				'message' => __( 'Installation was already deactivated.', 'wpcalibrate-license-manager' ),
			);
		}

		$this->activation_repo->delete( (int) $existing->id );
		$this->license_repo->recalculate_activation_count( (int) $license->id );

		$this->logger->log(
			'site_deactivated',
			$license->id,
			$product_id,
			$existing->site_domain,
			array(
				'site_identifier' => $site_identifier,
			)
		);

		return array(
			'success' => true,
			'code'    => 'deactivated',
			'message' => __( 'License successfully deactivated for this site.', 'wpcalibrate-license-manager' ),
		);
	}

	/**
	 * Reset all activations for a license (Admin Action).
	 *
	 * @param int $license_id
	 * @return int Number of activations removed.
	 */
	public function reset_activations( int $license_id ): int {
		$deleted = $this->activation_repo->delete_all_by_license_id( $license_id );
		$this->license_repo->recalculate_activation_count( $license_id );

		$this->logger->log(
			'activations_reset',
			$license_id,
			null,
			'',
			array( 'deleted_count' => $deleted )
		);

		return $deleted;
	}
}
