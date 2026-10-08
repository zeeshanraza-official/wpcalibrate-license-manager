<?php
/**
 * Plugin Name:       WPCalibrate License Manager
 * Plugin URI:        https://wpcalibrate.com
 * Description:       Centralized licensing authority, product versioning, secure private updates, and automated license distribution for WPCalibrate plugins.
 * Version:           1.1.2
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-license-manager
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Tested up to:      7.1.3
 * Requires PHP:      8.2
 * WC requires at least: 9.0
 * WC tested up to:   11.2.0
 * Update URI:        https://github.com/zeeshanraza-official/wpcalibrate-license-manager/
 * GitHub Plugin URI: zeeshanraza-official/wpcalibrate-license-manager
 * Primary Branch:    main
 * Release Asset:     true
 *
 * @package WPCalibrate\LicenseManager
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Minimum PHP and WordPress constraints.
if ( ! defined( 'WPCALIBRATE_LM_MIN_PHP_VERSION' ) ) {
	define( 'WPCALIBRATE_LM_MIN_PHP_VERSION', '8.2' );
}
if ( ! defined( 'WPCALIBRATE_LM_MIN_WP_VERSION' ) ) {
	define( 'WPCALIBRATE_LM_MIN_WP_VERSION', '6.5' );
}

// Core Plugin Constants.
if ( ! defined( 'WPCALIBRATE_LM_VERSION' ) ) {
	define( 'WPCALIBRATE_LM_VERSION', '1.1.2' );
}
if ( ! defined( 'WPCALIBRATE_LM_DB_VERSION' ) ) {
	define( 'WPCALIBRATE_LM_DB_VERSION', '1.0.0' );
}
if ( ! defined( 'WPCALIBRATE_LM_FILE' ) ) {
	define( 'WPCALIBRATE_LM_FILE', __FILE__ );
}
if ( ! defined( 'WPCALIBRATE_LM_DIR' ) ) {
	define( 'WPCALIBRATE_LM_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WPCALIBRATE_LM_URL' ) ) {
	define( 'WPCALIBRATE_LM_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WPCALIBRATE_LM_BASENAME' ) ) {
	define( 'WPCALIBRATE_LM_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! function_exists( __NAMESPACE__ . '\wpcalibrate_lm_check_requirements' ) ) {
	/**
	 * Check system requirements before running.
	 *
	 * @return bool True if requirements are satisfied.
	 */
	function wpcalibrate_lm_check_requirements(): bool {
	global $wp_version;

	if ( version_compare( PHP_VERSION, WPCALIBRATE_LM_MIN_PHP_VERSION, '<' ) ) {
		add_action(
			'admin_notices',
			static function () {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					sprintf(
						/* translators: 1: Plugin name, 2: Required PHP version, 3: Current PHP version */
						esc_html__( '%1$s requires PHP %2$s or higher. Your server is running PHP %3$s.', 'wpcalibrate-license-manager' ),
						'<strong>' . esc_html__( 'WPCalibrate License Manager', 'wpcalibrate-license-manager' ) . '</strong>',
						esc_html( WPCALIBRATE_LM_MIN_PHP_VERSION ),
						esc_html( PHP_VERSION )
					)
				);
			}
		);
		return false;
	}

	$current_wp_version = preg_replace( '/-.*$/', '', $wp_version ?? '0.0.0' );
	if ( version_compare( (string) $current_wp_version, WPCALIBRATE_LM_MIN_WP_VERSION, '<' ) ) {
		add_action(
			'admin_notices',
			static function () {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					sprintf(
						/* translators: 1: Plugin name, 2: Required WordPress version */
						esc_html__( '%1$s requires WordPress %2$s or higher.', 'wpcalibrate-license-manager' ),
						'<strong>' . esc_html__( 'WPCalibrate License Manager', 'wpcalibrate-license-manager' ) . '</strong>',
						esc_html( WPCALIBRATE_LM_MIN_WP_VERSION )
					)
				);
			}
		);
		return false;
	}

	return true;
	}
}

if ( ! wpcalibrate_lm_check_requirements() ) {
	return;
}

// Require and register PSR-4 Autoloader.
require_once WPCALIBRATE_LM_DIR . 'includes/Autoloader.php';
Autoloader::register( WPCALIBRATE_LM_DIR . 'includes' );

// Declare WooCommerce HPOS compatibility early.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				WPCALIBRATE_LM_FILE,
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				WPCALIBRATE_LM_FILE,
				true
			);
		}
	}
);

// Activation and deactivation hooks.
register_activation_hook(
	__FILE__,
	static function () {
		\WPCalibrate\LicenseManager\Database\Installer::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		\WPCalibrate\LicenseManager\Database\Installer::deactivate();
	}
);

// Bootstrap plugin instance.
add_action(
	'plugins_loaded',
	static function () {
		Plugin::instance()->boot();
	}
);
