<?php
/**
 * SDK License Cache & Outage Grace Period Handler.
 *
 * @package WPCalibrate\LicensingSDK\Cache
 */

declare(strict_types=1);

namespace WPCalibrate\LicensingSDK\Cache;

use WPCalibrate\LicensingSDK\Config;
use WPCalibrate\LicensingSDK\License\Status;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LicenseCache
 */
final class LicenseCache {

	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	private function get_transient_key(): string {
		return 'wpcalibrate_lic_cache_' . hash( 'crc32b', $this->config->get_plugin_slug() );
	}

	/**
	 * Retrieve cached validation state.
	 *
	 * @return array|null
	 */
	public function get(): ?array {
		$cached = get_transient( $this->get_transient_key() );
		return is_array( $cached ) ? $cached : null;
	}

	/**
	 * Store validation state in cache with 24h expiration.
	 *
	 * @param array $data
	 * @param int   $ttl_hours
	 * @return void
	 */
	public function set( array $data, int $ttl_hours = 24 ): void {
		$data['cached_at'] = time();
		// Set grace period cutoff to 7 days from now if currently active.
		if ( ( $data['status'] ?? '' ) === Status::ACTIVE ) {
			$data['grace_until'] = time() + ( 7 * DAY_IN_SECONDS );
		}

		set_transient( $this->get_transient_key(), $data, max( 1, $ttl_hours ) * HOUR_IN_SECONDS );
	}

	/**
	 * Invalidate cache.
	 *
	 * @return void
	 */
	public function flush(): void {
		delete_transient( $this->get_transient_key() );
	}

	/**
	 * Handle network outage using grace period.
	 *
	 * @param array $stored_license
	 * @return array
	 */
	public function handle_network_failure( array $stored_license ): array {
		$grace_until = (int) ( $stored_license['grace_until'] ?? 0 );

		if ( $grace_until > time() ) {
			return array(
				'valid'              => true,
				'status'             => Status::ACTIVE,
				'in_grace_period'    => true,
				'network_unverified' => true,
				'message'            => __( 'Licensing server unreachable; functioning within grace period.', 'wpcalibrate-license-manager' ),
				'expires_at'         => $stored_license['expires_at'] ?? null,
				'is_lifetime'        => ! empty( $stored_license['is_lifetime'] ),
			);
		}

		return array(
			'valid'              => false,
			'status'             => Status::NETWORK_ERROR,
			'in_grace_period'    => false,
			'network_unverified' => true,
			'message'            => __( 'Licensing server unreachable and grace period has expired.', 'wpcalibrate-license-manager' ),
			'expires_at'         => $stored_license['expires_at'] ?? null,
			'is_lifetime'        => ! empty( $stored_license['is_lifetime'] ),
		);
	}
}
