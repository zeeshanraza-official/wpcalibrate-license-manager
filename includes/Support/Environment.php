<?php
/**
 * Environment & Site Identity Helper.
 *
 * @package WPCalibrate\LicenseManager\Support
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Environment
 */
final class Environment {

	/**
	 * Normalize domain string from URL or host.
	 *
	 * @param string $raw_url_or_domain
	 * @return string Normalized lowercase domain (e.g., 'example.com').
	 */
	public static function normalize_domain( string $raw_url_or_domain ): string {
		$parsed = wp_parse_url( $raw_url_or_domain );
		$host   = $parsed['host'] ?? $raw_url_or_domain;

		// Strip port if present in raw string.
		$host = preg_replace( '/:\d+$/', '', $host );

		// Lowercase and trim.
		$host = strtolower( trim( (string) $host ) );

		// Strip leading www.
		if ( str_starts_with( $host, 'www.' ) ) {
			$host = substr( $host, 4 );
		}

		return $host;
	}

	/**
	 * Determine if domain matches staging or development rules.
	 *
	 * @param string $domain Normalized domain.
	 * @return bool True if recognized as staging/development.
	 */
	public static function is_staging_domain( string $domain ): bool {
		$domain = self::normalize_domain( $domain );

		// Hardcoded known local environments.
		if ( in_array( $domain, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
			return true;
		}

		$settings = get_option( 'wpcalibrate_lm_settings', array() );
		$patterns = (string) ( $settings['staging_patterns'] ?? "localhost\n127.0.0.1\n*.local\n*.test\n*.staging.*\n*-staging.*\n*.dev" );
		if ( empty( trim( $patterns ) ) ) {
			$patterns = "localhost\n127.0.0.1\n*.local\n*.test\n*.staging.*\n*-staging.*\n*.dev";
		}
		$lines    = explode( "\n", str_replace( "\r", '', $patterns ) );

		foreach ( $lines as $line ) {
			$pattern = trim( $line );
			if ( empty( $pattern ) ) {
				continue;
			}

			// Convert wildcard pattern to regex.
			$regex = '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '$#i';
			if ( preg_match( $regex, $domain ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Compute stable site identifier hash.
	 *
	 * @param string $site_identifier Client provided site identifier or site URL.
	 * @return string 64-character SHA-256 string.
	 */
	public static function hash_site_identifier( string $site_identifier ): string {
		return hash( 'sha256', trim( $site_identifier ) );
	}
}
