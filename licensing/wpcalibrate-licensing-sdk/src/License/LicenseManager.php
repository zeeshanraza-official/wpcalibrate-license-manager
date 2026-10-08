<?php
/**
 * SDK Core License Manager.
 *
 * @package WPCalibrate\LicensingSDK\License
 */

declare(strict_types=1);

namespace WPCalibrate\LicensingSDK\License;

use WPCalibrate\LicensingSDK\Api\Transport;
use WPCalibrate\LicensingSDK\Cache\LicenseCache;
use WPCalibrate\LicensingSDK\Config;
use WPCalibrate\LicensingSDK\Site\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LicenseManager
 */
final class LicenseManager {

	private Config $config;
	private Transport $transport;
	private LicenseCache $cache;

	public function __construct( Config $config, Transport $transport, LicenseCache $cache ) {
		$this->config    = $config;
		$this->transport = $transport;
		$this->cache     = $cache;
	}

	/**
	 * Retrieve stored license state array.
	 *
	 * @return array
	 */
	public function get_license_data(): array {
		$data = get_option( $this->config->get_storage_key(), array() );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Check if license is currently active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		$data = $this->get_license_data();
		if ( empty( $data['license_key'] ) ) {
			return false;
		}

		$status = $data['status'] ?? Status::UNLICENSED;
		if ( Status::ACTIVE !== $status ) {
			return false;
		}

		if ( empty( $data['is_lifetime'] ) && ! empty( $data['expires_at'] ) ) {
			if ( strtotime( $data['expires_at'] ) < time() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Activate a license key with the server.
	 *
	 * @param string $raw_key Plaintext license key.
	 * @return array
	 */
	public function activate( string $raw_key ): array {
		$clean_key = strtoupper( trim( sanitize_text_field( $raw_key ) ) );
		if ( empty( $clean_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please enter a valid license key.', 'wpcalibrate-license-manager' ),
			);
		}

		$payload = array(
			'license_key'     => $clean_key,
			'product_id'      => $this->config->get_product_id(),
			'site_url'        => SiteIdentity::get_site_url(),
			'site_identifier' => SiteIdentity::get_installation_id(),
			'wp_version'      => get_bloginfo( 'version' ),
			'plugin_version'  => $this->config->get_current_version(),
			'multisite'       => SiteIdentity::is_multisite(),
		);

		$response = $this->transport->post( 'activate', $payload );

		if ( ! $response['success'] ) {
			return array(
				'success' => false,
				'message' => $response['error'] ?: __( 'License activation failed.', 'wpcalibrate-license-manager' ),
			);
		}

		$data = $response['data'];

		$masked = strlen( $clean_key ) > 4
			? str_repeat( '*', strlen( $clean_key ) - 4 ) . substr( $clean_key, -4 )
			: $clean_key;

		$stored = array(
			'license_key'        => $clean_key,
			'license_key_masked' => $masked,
			'status'             => $data['license_status'] ?? Status::ACTIVE,
			'is_lifetime'        => ! empty( $data['is_lifetime'] ),
			'expires_at'         => $data['expires_at'] ?? null,
			'activation_limit'   => (int) ( $data['activation_limit'] ?? 1 ),
			'activation_count'   => (int) ( $data['activation_count'] ?? 1 ),
			'last_checked'       => time(),
			'grace_until'        => time() + ( 7 * DAY_IN_SECONDS ),
		);

		update_option( $this->config->get_storage_key(), $stored, false );
		$this->cache->flush();

		return array(
			'success' => true,
			'message' => $data['message'] ?? __( 'License activated successfully.', 'wpcalibrate-license-manager' ),
			'data'    => $stored,
		);
	}

	/**
	 * Deactivate the currently active license key.
	 *
	 * @return array
	 */
	public function deactivate(): array {
		$current = $this->get_license_data();
		$key     = $current['license_key'] ?? '';

		if ( ! empty( $key ) ) {
			$payload = array(
				'license_key'     => $key,
				'product_id'      => $this->config->get_product_id(),
				'site_identifier' => SiteIdentity::get_installation_id(),
			);

			$this->transport->post( 'deactivate', $payload );
		}

		delete_option( $this->config->get_storage_key() );
		$this->cache->flush();

		return array(
			'success' => true,
			'message' => __( 'License deactivated successfully.', 'wpcalibrate-license-manager' ),
		);
	}

	/**
	 * Validate license status (cached, daily check).
	 *
	 * @param bool $force_remote Force network request.
	 * @return array
	 */
	public function validate( bool $force_remote = false ): array {
		$stored = $this->get_license_data();
		if ( empty( $stored['license_key'] ) ) {
			return array(
				'valid'   => false,
				'status'  => Status::UNLICENSED,
				'message' => __( 'No license configured.', 'wpcalibrate-license-manager' ),
			);
		}

		// Check cache unless forced.
		if ( ! $force_remote ) {
			$cached = $this->cache->get();
			if ( $cached ) {
				return $cached;
			}
		}

		$payload = array(
			'license_key'     => $stored['license_key'],
			'product_id'      => $this->config->get_product_id(),
			'site_identifier' => SiteIdentity::get_installation_id(),
			'site_url'        => SiteIdentity::get_site_url(),
		);

		$response = $this->transport->post( 'validate', $payload );

		if ( ! $response['success'] ) {
			if ( 0 === $response['http_code'] ) {
				// Server unreachable -> trigger grace period logic.
				return $this->cache->handle_network_failure( $stored );
			}

			// Logical error from server (e.g. invalid, revoked, expired).
			$status          = $response['data']['status'] ?? Status::INVALID;
			$stored['status'] = $status;
			update_option( $this->config->get_storage_key(), $stored, false );

			$result = array(
				'valid'   => false,
				'status'  => $status,
				'message' => $response['error'] ?: __( 'License validation failed.', 'wpcalibrate-license-manager' ),
			);
			$this->cache->set( $result, 6 );
			return $result;
		}

		$data = $response['data'];

		$stored['status']           = $data['status'] ?? Status::ACTIVE;
		$stored['is_lifetime']      = ! empty( $data['is_lifetime'] );
		$stored['expires_at']       = $data['expires_at'] ?? null;
		$stored['activation_limit'] = (int) ( $data['activation_limit'] ?? 1 );
		$stored['activation_count'] = (int) ( $data['activation_count'] ?? 1 );
		$stored['last_checked']     = time();
		$stored['grace_until']      = time() + ( 7 * DAY_IN_SECONDS );

		update_option( $this->config->get_storage_key(), $stored, false );

		$result = array(
			'valid'       => ! empty( $data['valid'] ),
			'status'      => $stored['status'],
			'is_lifetime' => $stored['is_lifetime'],
			'expires_at'  => $stored['expires_at'],
			'message'     => $data['message'] ?? '',
		);

		$this->cache->set( $result, 24 );
		return $result;
	}
}
