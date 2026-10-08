<?php
/**
 * Versioned Database Migrator.
 *
 * @package WPCalibrate\LicenseManager\Database
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Migrator
 */
final class Migrator {

	/**
	 * Run all pending migrations.
	 *
	 * @return bool True on success.
	 */
	public static function run_migrations(): bool {
		$installed_version = (string) get_option( 'wpcalibrate_lm_db_version', '0.0.0' );

		// Load dbDelta function if not loaded.
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$schemas = Schema::get_schemas();
		foreach ( $schemas as $table_key => $sql ) {
			dbDelta( $sql );
		}

		// Update database version in options.
		update_option( 'wpcalibrate_lm_db_version', \WPCALIBRATE_LM_DB_VERSION, false );

		return true;
	}

	/**
	 * Check if database migration is required.
	 *
	 * @return bool
	 */
	public static function needs_migration(): bool {
		$installed_version = (string) get_option( 'wpcalibrate_lm_db_version', '0.0.0' );
		return version_compare( $installed_version, \WPCALIBRATE_LM_DB_VERSION, '<' );
	}
}
