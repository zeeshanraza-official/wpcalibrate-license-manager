<?php
/**
 * WPCalibrate License Manager Uninstallation Handler.
 *
 * Fired when the plugin is deleted via the WordPress admin plugins screen.
 *
 * @package WPCalibrate\LicenseManager
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Ensure the request is coming from WordPress uninstall process.
if ( WP_UNINSTALL_PLUGIN !== 'wpcalibrate-license-manager/wpcalibrate-license-manager.php'
	&& WP_UNINSTALL_PLUGIN !== basename( __DIR__ ) . '/' . basename( __FILE__ )
	&& ! str_contains( WP_UNINSTALL_PLUGIN, 'wpcalibrate-license-manager.php' ) ) {
	exit;
}

// Check data retention policy.
$retention_setting = get_option( 'wpcalibrate_lm_data_retention_policy', 'preserve' );

// Only delete if administrator explicitly configured permanent removal.
if ( 'delete_all' === $retention_setting ) {
	global $wpdb;

	// Drop custom tables belonging exclusively to WPCalibrate License Manager.
	$tables = array(
		$wpdb->prefix . 'wpcalibrate_license_events',
		$wpdb->prefix . 'wpcalibrate_activations',
		$wpdb->prefix . 'wpcalibrate_licenses',
		$wpdb->prefix . 'wpcalibrate_versions',
		$wpdb->prefix . 'wpcalibrate_products',
	);

	foreach ( $tables as $table ) {
		// Table names are strictly allowlisted above with dynamic prefix.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	// Delete plugin options.
	delete_option( 'wpcalibrate_lm_db_version' );
	delete_option( 'wpcalibrate_lm_settings' );
	delete_option( 'wpcalibrate_lm_data_retention_policy' );
	delete_option( 'wpcalibrate_lm_secret_key' );

	// Delete transients.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_wpcalibrate_lm_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_wpcalibrate_lm_' ) . '%'
		)
	);

	// Clear scheduled cron actions.
	wp_clear_scheduled_hook( 'wpcalibrate_lm_daily_maintenance' );
}
