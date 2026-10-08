<?php
/**
 * Privacy-Conscious Audit Logger.
 *
 * @package WPCalibrate\LicenseManager\Logging
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Logging;

use WPCalibrate\LicenseManager\Database\Repositories\EventRepository;
use WPCalibrate\LicenseManager\Domain\AuditEvent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AuditLogger
 */
final class AuditLogger {

	/**
	 * Event Repository.
	 *
	 * @var EventRepository
	 */
	private EventRepository $event_repo;

	/**
	 * Constructor.
	 *
	 * @param EventRepository $event_repo
	 */
	public function __construct( EventRepository $event_repo ) {
		$this->event_repo = $event_repo;
	}

	/**
	 * Record a safe audit event.
	 *
	 * @param string      $event_type   Type of event (e.g., 'activated', 'revoked').
	 * @param int|null    $license_id   License ID if applicable.
	 * @param int|null    $product_id   Product ID if applicable.
	 * @param string      $site_domain  Domain of the site.
	 * @param array       $context_data Extra context data (will be sanitized and masked).
	 * @param string|null $ip_address   Client IP address.
	 * @return int Inserted event ID.
	 */
	public function log(
		string $event_type,
		?int $license_id = null,
		?int $product_id = null,
		string $site_domain = '',
		array $context_data = array(),
		?string $ip_address = null
	): int {
		// Clean and mask sensitive information before storing.
		$sanitized_context = $this->mask_sensitive_data( $context_data );
		$context_json      = ! empty( $sanitized_context ) ? wp_json_encode( $sanitized_context ) : null;

		$ip = $ip_address ?? $this->get_client_ip();

		$event = new AuditEvent(
			array(
				'license_id'   => $license_id,
				'product_id'   => $product_id,
				'event_type'   => $event_type,
				'site_domain'  => sanitize_text_field( $site_domain ),
				'ip_address'   => sanitize_text_field( $ip ),
				'user_id'      => get_current_user_id() ?: null,
				'context_data' => $context_json,
			)
		);

		$event_id = $this->event_repo->insert( $event );

		// Also mirror to WooCommerce logger if available and enabled.
		if ( function_exists( 'wc_get_logger' ) ) {
			$wc_logger = wc_get_logger();
			$wc_logger->info(
				sprintf(
					'[%s] Event: %s | Product ID: %s | Domain: %s',
					'WPCalibrate-LM',
					$event_type,
					(string) ( $product_id ?? 'N/A' ),
					$site_domain
				),
				array( 'source' => 'wpcalibrate-license-manager' )
			);
		}

		return $event_id;
	}

	/**
	 * Mask sensitive keys in data array.
	 *
	 * @param array $data
	 * @return array
	 */
	public function mask_sensitive_data( array $data ): array {
		$sensitive_keys = array( 'key', 'license_key', 'token', 'password', 'secret', 'auth', 'download_token' );

		foreach ( $data as $k => $v ) {
			if ( is_array( $v ) ) {
				$data[ $k ] = $this->mask_sensitive_data( $v );
				continue;
			}

			if ( is_string( $v ) ) {
				$lower_k = strtolower( (string) $k );
				foreach ( $sensitive_keys as $sensitive_key ) {
					if ( str_contains( $lower_k, $sensitive_key ) ) {
						$data[ $k ] = $this->mask_string( $v );
						break;
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Mask a sensitive string to last 4 characters.
	 *
	 * @param string $str
	 * @return string
	 */
	public function mask_string( string $str ): string {
		$len = strlen( $str );
		if ( $len <= 4 ) {
			return str_repeat( '*', $len );
		}
		return str_repeat( '*', $len - 4 ) . substr( $str, -4 );
	}

	/**
	 * Retrieve client IP address safely.
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) ?: '';
		}
		return '';
	}

	/**
	 * Prune old audit events based on settings.
	 *
	 * @return int Number of rows pruned.
	 */
	public function prune_old_events(): int {
		$settings       = get_option( 'wpcalibrate_lm_settings', array() );
		$retention_days = max( 7, (int) ( $settings['log_retention_days'] ?? 90 ) );
		return $this->event_repo->prune_older_than( $retention_days );
	}
}
