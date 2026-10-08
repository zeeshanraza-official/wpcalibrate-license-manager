<?php
/**
 * REST API Rate Limiter using Transients.
 *
 * @package WPCalibrate\LicenseManager\Api
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RateLimiter
 */
final class RateLimiter {

	/**
	 * Check and increment rate limit for the client IP or identifier.
	 *
	 * @param string $identifier Client IP or request identifier.
	 * @return bool True if within allowed limit, false if rate limit exceeded.
	 */
	public static function check( string $identifier ): bool {
		$settings       = get_option( 'wpcalibrate_lm_settings', array() );
		$limit          = max( 10, (int) ( $settings['rate_limit_requests'] ?? 60 ) );
		$window_seconds = max( 10, (int) ( $settings['rate_limit_window_seconds'] ?? 60 ) );

		$transient_key = 'wpcalibrate_lm_rl_' . hash( 'crc32b', $identifier );
		$current_count = (int) get_transient( $transient_key );

		if ( false === $current_count || 0 === $current_count ) {
			set_transient( $transient_key, 1, $window_seconds );
			return true;
		}

		if ( $current_count >= $limit ) {
			return false;
		}

		set_transient( $transient_key, $current_count + 1, $window_seconds );
		return true;
	}

	/**
	 * Get client IP for rate limiting.
	 *
	 * @return string
	 */
	public static function get_client_ip(): string {
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) ?: 'unknown_ip';
		}
		return 'unknown_ip';
	}
}
