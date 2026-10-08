<?php
/**
 * Database Schema Definitions.
 *
 * @package WPCalibrate\LicenseManager\Database
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Schema
 */
final class Schema {

	/**
	 * Get table name with dynamic WordPress prefix.
	 *
	 * @param string $table_name Raw table name without prefix.
	 * @return string Full prefixed table name.
	 */
	public static function get_table_name( string $table_name ): string {
		global $wpdb;
		return $wpdb->prefix . $table_name;
	}

	/**
	 * Get the SQL schema for all custom tables.
	 *
	 * @return array<string, string> Array of table name => CREATE TABLE SQL.
	 */
	public static function get_schemas(): array {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_products    = self::get_table_name( 'wpcalibrate_products' );
		$table_licenses    = self::get_table_name( 'wpcalibrate_licenses' );
		$table_activations = self::get_table_name( 'wpcalibrate_activations' );
		$table_versions    = self::get_table_name( 'wpcalibrate_versions' );
		$table_events      = self::get_table_name( 'wpcalibrate_license_events' );

		return array(
			'wpcalibrate_products' => "CREATE TABLE {$table_products} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				slug varchar(100) NOT NULL,
				current_version varchar(50) NOT NULL DEFAULT '1.0.0',
				min_wp_version varchar(20) NOT NULL DEFAULT '6.5',
				tested_wp_version varchar(20) NOT NULL DEFAULT '7.1.3',
				min_php_version varchar(20) NOT NULL DEFAULT '8.2',
				requires_wc tinyint(1) NOT NULL DEFAULT 0,
				wc_product_id bigint(20) unsigned DEFAULT NULL,
				changelog longtext DEFAULT NULL,
				renewal_url varchar(500) DEFAULT '',
				status varchar(20) NOT NULL DEFAULT 'active',
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY uq_slug (slug),
				KEY ix_status (status),
				KEY ix_wc_product_id (wc_product_id)
			) {$charset_collate};",

			'wpcalibrate_licenses' => "CREATE TABLE {$table_licenses} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				license_key_hash varchar(64) NOT NULL,
				license_key_masked varchar(40) NOT NULL,
				license_key_encrypted text DEFAULT NULL,
				product_id bigint(20) unsigned NOT NULL,
				customer_id bigint(20) unsigned DEFAULT NULL,
				customer_email varchar(100) DEFAULT '',
				order_id bigint(20) unsigned DEFAULT NULL,
				subscription_id bigint(20) unsigned DEFAULT NULL,
				activation_limit int(10) unsigned NOT NULL DEFAULT 1,
				activation_count int(10) unsigned NOT NULL DEFAULT 0,
				is_lifetime tinyint(1) NOT NULL DEFAULT 0,
				expires_at datetime DEFAULT NULL,
				status varchar(20) NOT NULL DEFAULT 'active',
				admin_notes text DEFAULT NULL,
				metadata longtext DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY uq_key_hash (license_key_hash),
				KEY ix_product_id (product_id),
				KEY ix_status (status),
				KEY ix_expires_at (expires_at),
				KEY ix_customer_id (customer_id),
				KEY ix_order_id (order_id)
			) {$charset_collate};",

			'wpcalibrate_activations' => "CREATE TABLE {$table_activations} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				license_id bigint(20) unsigned NOT NULL,
				product_id bigint(20) unsigned NOT NULL,
				site_identifier varchar(64) NOT NULL,
				site_url varchar(500) NOT NULL,
				site_domain varchar(255) NOT NULL,
				site_ip varchar(45) DEFAULT '',
				wp_version varchar(20) DEFAULT '',
				plugin_version varchar(20) DEFAULT '',
				is_staging tinyint(1) NOT NULL DEFAULT 0,
				multisite tinyint(1) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				last_validated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY uq_license_site (license_id, site_identifier),
				KEY ix_product_id (product_id),
				KEY ix_site_identifier (site_identifier),
				KEY ix_site_domain (site_domain),
				KEY ix_is_staging (is_staging)
			) {$charset_collate};",

			'wpcalibrate_versions' => "CREATE TABLE {$table_versions} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				product_id bigint(20) unsigned NOT NULL,
				version varchar(50) NOT NULL,
				package_path varchar(500) NOT NULL,
				package_size bigint(20) unsigned NOT NULL DEFAULT 0,
				package_hash varchar(64) NOT NULL DEFAULT '',
				min_wp_version varchar(20) NOT NULL DEFAULT '6.5',
				tested_wp_version varchar(20) NOT NULL DEFAULT '7.1.3',
				min_php_version varchar(20) NOT NULL DEFAULT '8.2',
				changelog longtext DEFAULT NULL,
				upgrade_notice text DEFAULT NULL,
				status varchar(20) NOT NULL DEFAULT 'published',
				release_date datetime NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY uq_prod_ver (product_id, version),
				KEY ix_product_id (product_id),
				KEY ix_status (status)
			) {$charset_collate};",

			'wpcalibrate_license_events' => "CREATE TABLE {$table_events} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				license_id bigint(20) unsigned DEFAULT NULL,
				product_id bigint(20) unsigned DEFAULT NULL,
				event_type varchar(50) NOT NULL,
				site_domain varchar(255) DEFAULT '',
				ip_address varchar(45) DEFAULT '',
				user_id bigint(20) unsigned DEFAULT NULL,
				context_data text DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY ix_license_id (license_id),
				KEY ix_product_id (product_id),
				KEY ix_event_type (event_type),
				KEY ix_created_at (created_at)
			) {$charset_collate};",
		);
	}
}
