<?php
/**
 * Cryptographic Token Manager for Authorized Downloads.
 *
 * @package WPCalibrate\LicenseManager\Security
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TokenManager
 */
final class TokenManager {

	/**
	 * Get server secret key.
	 *
	 * @return string
	 */
	private static function get_secret(): string {
		$secret = (string) get_option( 'wpcalibrate_lm_secret_key', '' );
		if ( empty( $secret ) ) {
			// Fallback to wp_salt if option is empty.
			$secret = hash( 'sha256', wp_salt( 'auth' ) );
		}
		return $secret;
	}

	/**
	 * Generate a short-lived HMAC-SHA256 signed download token.
	 *
	 * @param int    $product_id      Target product ID.
	 * @param int    $version_id      Target version ID.
	 * @param int    $license_id      Authorizing license ID.
	 * @param string $site_identifier Site identity hash.
	 * @param int    $ttl_seconds     Token time-to-live in seconds.
	 * @return string Base64URL-encoded token string.
	 */
	public static function generate_download_token(
		int $product_id,
		int $version_id,
		int $license_id,
		string $site_identifier = '',
		int $ttl_seconds = 900
	): string {
		$payload = array(
			'pid'   => $product_id,
			'vid'   => $version_id,
			'lid'   => $license_id,
			'site'  => $site_identifier,
			'exp'   => time() + $ttl_seconds,
			'nonce' => bin2hex( random_bytes( 8 ) ),
		);

		$payload_json = (string) wp_json_encode( $payload );
		$payload_b64  = self::base64url_encode( $payload_json );
		$signature    = hash_hmac( 'sha256', $payload_b64, self::get_secret() );

		return $payload_b64 . '.' . $signature;
	}

	/**
	 * Verify and parse a download token.
	 *
	 * @param string   $token           Token string from request.
	 * @param int|null $expected_product Expected product ID to prevent cross-product attacks.
	 * @return array|null Returns decoded payload array on success, null on invalid or expired token.
	 */
	public static function verify_download_token( string $token, ?int $expected_product = null ): ?array {
		if ( ! str_contains( $token, '.' ) ) {
			return null;
		}

		$parts = explode( '.', $token, 2 );
		if ( count( $parts ) !== 2 ) {
			return null;
		}

		list( $payload_b64, $provided_signature ) = $parts;
		$expected_signature = hash_hmac( 'sha256', $payload_b64, self::get_secret() );

		// Constant-time signature verification.
		if ( ! hash_equals( $expected_signature, $provided_signature ) ) {
			return null;
		}

		$payload_json = self::base64url_decode( $payload_b64 );
		if ( false === $payload_json ) {
			return null;
		}

		$payload = json_decode( $payload_json, true );
		if ( ! is_array( $payload ) || empty( $payload['exp'] ) ) {
			return null;
		}

		// Check expiry.
		if ( time() > (int) $payload['exp'] ) {
			return null;
		}

		// Prevent Product A token from downloading Product B.
		if ( null !== $expected_product && (int) $payload['pid'] !== $expected_product ) {
			return null;
		}

		return $payload;
	}

	/**
	 * Base64URL encode helper.
	 *
	 * @param string $data
	 * @return string
	 */
	private static function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64URL decode helper.
	 *
	 * @param string $data
	 * @return string|false
	 */
	private static function base64url_decode( string $data ): string|false {
		return base64_decode( strtr( $data, '-_', '+/' ) . str_repeat( '=', 3 - ( 3 + strlen( $data ) ) % 4 ) );
	}
}
