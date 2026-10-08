<?php
/**
 * Plugin Installer for Activation and Deactivation Lifecycles.
 *
 * @package WPCalibrate\LicenseManager\Database
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Installer
 */
final class Installer {

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Run database migrations.
		Migrator::run_migrations();

		// Generate random cryptographic server secret key if not yet generated.
		if ( ! get_option( 'wpcalibrate_lm_secret_key' ) ) {
			$secret = bin2hex( random_bytes( 32 ) );
			add_option( 'wpcalibrate_lm_secret_key', $secret, '', 'no' );
		}

		// Initialize default settings if not exists.
		if ( false === get_option( 'wpcalibrate_lm_settings' ) ) {
			$defaults = array(
				'validation_cache_interval_hours' => 24,
				'grace_period_days'              => 7,
				'default_activation_limit'        => 1,
				'default_expiry_days'             => 365,
				'staging_patterns'                => "localhost\n127.0.0.1\n*.local\n*.test\n*.staging.*\n*-staging.*\n*.dev",
				'download_token_ttl_seconds'      => 900, // 15 minutes
				'logging_level'                   => 'info',
				'data_retention_policy'           => 'preserve',
				'log_retention_days'              => 90,
				'wc_auto_license'                 => 1,
				'wc_refund_action'                => 'suspend', // 'suspend', 'revoke', 'none'
				'rate_limit_requests'             => 60,
				'rate_limit_window_seconds'       => 60,
			);
			add_option( 'wpcalibrate_lm_settings', $defaults, '', 'no' );
		}

		// Schedule daily maintenance cron event.
		if ( ! wp_next_scheduled( 'wpcalibrate_lm_daily_maintenance' ) ) {
			wp_schedule_event( time(), 'daily', 'wpcalibrate_lm_daily_maintenance' );
		}
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear scheduled cron events.
		wp_clear_scheduled_hook( 'wpcalibrate_lm_daily_maintenance' );

		// Flush temporary transients.
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_wpcalibrate_lm_' ) . '%'
			)
		);

		// Note: All database tables, products, licenses, and settings are strictly preserved on deactivation.
	}
}
