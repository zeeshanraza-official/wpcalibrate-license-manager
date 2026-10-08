<?php
/**
 * SDK Site Identity Helper.
 *
 * Provides a stable unique installation identifier and normalized URL.
 *
 * @package WPCalibrate\LicensingSDK\Site
 */

declare(strict_types=1);

namespace WPCalibrate\LicensingSDK\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SiteIdentity
 */
final class SiteIdentity {

	/**
	 * Retrieve or generate a persistent local installation identifier.
	 *
	 * @return string 64-char hex string.
	 */
	public static function get_installation_id(): string {
		$id = (string) get_option( 'wpcalibrate_installation_id', '' );
		if ( empty( $id ) ) {
			$raw = home_url() . '|' . wp_salt( 'nonce' ) . '|' . microtime( true );
			$id  = hash( 'sha256', $raw );
			add_option( 'wpcalibrate_installation_id', $id, '', 'no' );
		}
		return $id;
	}

	/**
	 * Get normalized site URL.
	 *
	 * @return string
	 */
	public static function get_site_url(): string {
		return untrailingslashit( home_url() );
	}

	/**
	 * Check if multisite installation.
	 *
	 * @return bool
	 */
	public static function is_multisite(): bool {
		return is_multisite();
	}
}
