<?php
/**
 * Minimal Host Plugin Integration Example.
 *
 * Demonstrates how a commercial WPCalibrate plugin includes and utilizes the SDK.
 *
 * @package WPCalibrate\LicensingSDK\Examples
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Require SDK bootstrap
require_once dirname( __FILE__, 2 ) . '/bootstrap.php';

use WPCalibrate\LicensingSDK\Client;
use WPCalibrate\LicensingSDK\Config;

/**
 * Bootstrap commercial plugin licensing.
 */
function wpcalibrate_example_addon_init_licensing(): Client {
	$config = new Config(
		array(
			'product_id'          => 101,
			'plugin_slug'         => 'wpcalibrate-analytics-pro',
			'plugin_name'         => 'WPCalibrate Analytics Pro',
			'current_version'     => '1.0.0',
			'main_plugin_file'    => __FILE__,
			'license_server_url'  => 'https://marketplace.wpcalibrate.com',
			'settings_page_slug'  => 'wpcalibrate-analytics-pro-settings',
			'required_capability' => 'manage_options',
			'text_domain'         => 'wpcalibrate-analytics-pro',
			'renewal_url'         => 'https://marketplace.wpcalibrate.com/checkout?renew=101',
		)
	);

	return Client::init( $config );
}

add_action( 'plugins_loaded', 'wpcalibrate_example_addon_init_licensing' );
