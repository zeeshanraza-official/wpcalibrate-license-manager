<?php
/**
 * SDK WordPress Private Plugin Updater.
 *
 * Integrates directly with core WordPress update transient and plugins_api.
 *
 * @package WPCalibrate\LicensingSDK\Updates
 */

declare(strict_types=1);

namespace WPCalibrate\LicensingSDK\Updates;

use stdClass;
use WPCalibrate\LicensingSDK\Api\Transport;
use WPCalibrate\LicensingSDK\Config;
use WPCalibrate\LicensingSDK\License\LicenseManager;
use WPCalibrate\LicensingSDK\Site\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PluginUpdater
 */
final class PluginUpdater {

	private Config $config;
	private Transport $transport;
	private LicenseManager $license_manager;

	public function __construct( Config $config, Transport $transport, LicenseManager $license_manager ) {
		$this->config          = $config;
		$this->transport       = $transport;
		$this->license_manager = $license_manager;
	}

	public function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'filter_update_plugins' ) );
		add_filter( 'plugins_api', array( $this, 'filter_plugins_api' ), 20, 3 );
	}

	/**
	 * Check for updates and inject into update_plugins transient.
	 *
	 * @param object|false $transient
	 * @return object|false
	 */
	public function filter_update_plugins( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}

		$plugin_file = plugin_basename( $this->config->get_main_plugin_file() );
		$update_info = $this->check_update();

		if ( ! $update_info || empty( $update_info['has_update'] ) ) {
			return $transient;
		}

		$res = new stdClass();
		$res->id            = 'wpcalibrate_' . $this->config->get_product_id();
		$res->slug          = $this->config->get_plugin_slug();
		$res->plugin        = $plugin_file;
		$res->new_version   = $update_info['latest_version'];
		$res->url           = $update_info['renewal_url'] ?: 'https://wpcalibrate.com';
		$res->package       = $update_info['package'] ?: '';
		$res->requires      = $update_info['requires_wp'] ?? '6.5';
		$res->tested        = $update_info['tested_wp'] ?? '7.1.3';
		$res->requires_php  = $update_info['requires_php'] ?? '8.2';
		$res->upgrade_notice = $update_info['upgrade_notice'] ?? '';

		if ( ! empty( $update_info['package'] ) ) {
			$transient->response[ $plugin_file ] = $res;
		} else {
			// Update exists but license is inactive/expired: include in no_update or response without package.
			$res->upgrade_notice = __( 'A valid, active license is required to download updates.', 'wpcalibrate-license-manager' );
			$transient->no_update[ $plugin_file ] = $res;
		}

		return $transient;
	}

	/**
	 * Hook plugins_api for view details modal.
	 *
	 * @param false|object|array $res
	 * @param string             $action
	 * @param object             $args
	 * @return false|object
	 */
	public function filter_plugins_api( $res, string $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $args->slug !== $this->config->get_plugin_slug() ) {
			return $res;
		}

		$update_info = $this->check_update();
		if ( ! $update_info ) {
			return $res;
		}

		$api_res                 = new stdClass();
		$api_res->name           = $this->config->get_plugin_name();
		$api_res->slug           = $this->config->get_plugin_slug();
		$api_res->version        = $update_info['latest_version'];
		$api_res->author         = '<a href="https://wpcalibrate.com">WPCalibrate</a>';
		$api_res->homepage       = 'https://wpcalibrate.com';
		$api_res->requires       = $update_info['requires_wp'] ?? '6.5';
		$api_res->tested         = $update_info['tested_wp'] ?? '7.1.3';
		$api_res->requires_php   = $update_info['requires_php'] ?? '8.2';
		$api_res->download_link  = $update_info['package'] ?? '';
		$api_res->sections       = array(
			'description' => sprintf(
				/* translators: %s: Plugin name */
				__( '%s commercial extension by WPCalibrate.', 'wpcalibrate-license-manager' ),
				$this->config->get_plugin_name()
			),
			'changelog'   => nl2br( (string) ( $update_info['changelog'] ?? __( 'Maintenance release.', 'wpcalibrate-license-manager' ) ) ),
		);

		return $api_res;
	}

	/**
	 * Query update server with 12h caching.
	 *
	 * @return array|null
	 */
	public function check_update(): ?array {
		$cache_key = 'wpcalibrate_upd_' . hash( 'crc32b', $this->config->get_plugin_slug() );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$license_data = $this->license_manager->get_license_data();
		$key          = $license_data['license_key'] ?? '';

		$payload = array(
			'product_id'        => $this->config->get_product_id(),
			'plugin_slug'       => $this->config->get_plugin_slug(),
			'installed_version' => $this->config->get_current_version(),
			'license_key'       => $key,
			'site_identifier'   => SiteIdentity::get_installation_id(),
			'site_url'          => SiteIdentity::get_site_url(),
		);

		$response = $this->transport->post( 'update-check', $payload );

		if ( ! $response['success'] ) {
			return null;
		}

		$data = $response['data'];
		set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );

		return $data;
	}
}
