<?php
/**
 * Cryptographic License Key Generator and Encryptor.
 *
 * @package WPCalibrate\LicenseManager\Licensing
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KeyGenerator
 */
final class KeyGenerator {

	/**
	 * License key prefix.
	 */
	public const KEY_PREFIX = 'WPC';

	/**
	 * Generate a new cryptographically secure license key bundle.
	 *
	 * @return array{plaintext: string, hash: string, masked: string, encrypted: string}
	 */
	public static function generate(): array {
		// 16 bytes of CSPRNG = 128 bits of entropy.
		$random_bytes = random_bytes( 16 );
		$hex          = strtoupper( bin2hex( $random_bytes ) ); // 32 hex chars

		// Format into WPC-XXXX-XXXX-XXXX-XXXX
		$chunks    = str_split( substr( $hex, 0, 16 ), 4 );
		$plaintext = self::KEY_PREFIX . '-' . implode( '-', $chunks );

		$hash      = self::hash_key( $plaintext );
		$masked    = self::mask_key( $plaintext );
		$encrypted = self::encrypt_key( $plaintext );

		return array(
			'plaintext' => $plaintext,
			'hash'      => $hash,
			'masked'    => $masked,
			'encrypted' => $encrypted,
		);
	}

	/**
	 * Hash license key for secure lookup.
	 *
	 * @param string $key Plaintext license key.
	 * @return string SHA-256 hash.
	 */
	public static function hash_key( string $key ): string {
		$normalized = self::normalize_key( $key );
		return hash( 'sha256', $normalized );
	}

	/**
	 * Mask a license key showing only prefix and last 4 characters.
	 * Example: WPC-****-****-****-7A8B
	 *
	 * @param string $key
	 * @return string
	 */
	public static function mask_key( string $key ): string {
		$normalized = self::normalize_key( $key );
		$parts      = explode( '-', $normalized );

		if ( count( $parts ) >= 2 ) {
			$prefix = array_shift( $parts );
			$last   = array_pop( $parts );
			$middle = array_fill( 0, count( $parts ), '****' );
			return $prefix . '-' . implode( '-', $middle ) . '-' . $last;
		}

		$len = strlen( $normalized );
		if ( $len <= 4 ) {
			return str_repeat( '*', $len );
		}

		return str_repeat( '*', $len - 4 ) . substr( $normalized, -4 );
	}

	/**
	 * Encrypt plaintext key with AES-256-CBC using server secret.
	 *
	 * @param string $plaintext
	 * @return string Base64 encoded payload with IV prepended.
	 */
	public static function encrypt_key( string $plaintext ): string {
		$secret = self::get_encryption_key();
		$iv_len = openssl_cipher_iv_length( 'aes-256-cbc' );
		$iv     = random_bytes( $iv_len );

		$encrypted = openssl_encrypt( $plaintext, 'aes-256-cbc', $secret, OPENSSL_RAW_DATA, $iv );
		if ( false === $encrypted ) {
			return '';
		}

		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt stored encrypted key.
	 *
	 * @param string $encrypted_bundle Base64 encoded payload.
	 * @return string|null Plaintext key on success, null on failure.
	 */
	public static function decrypt_key( string $encrypted_bundle ): ?string {
		$data   = base64_decode( $encrypted_bundle, true );
		$iv_len = openssl_cipher_iv_length( 'aes-256-cbc' );

		if ( false === $data || strlen( $data ) <= $iv_len ) {
			return null;
		}

		$secret    = self::get_encryption_key();
		$iv        = substr( $data, 0, $iv_len );
		$raw       = substr( $data, $iv_len );
		$decrypted = openssl_decrypt( $raw, 'aes-256-cbc', $secret, OPENSSL_RAW_DATA, $iv );

		return false !== $decrypted ? $decrypted : null;
	}

	/**
	 * Normalize license key formatting.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function normalize_key( string $key ): string {
		return strtoupper( trim( sanitize_text_field( $key ) ) );
	}

	/**
	 * Retrieve 32-byte binary key for AES-256.
	 *
	 * @return string
	 */
	private static function get_encryption_key(): string {
		$secret = (string) get_option( 'wpcalibrate_lm_secret_key', '' );
		if ( empty( $secret ) ) {
			$secret = hash( 'sha256', wp_salt( 'auth' ) );
		}
		return hash( 'sha256', $secret, true ); // 32-byte raw binary
	}
}
