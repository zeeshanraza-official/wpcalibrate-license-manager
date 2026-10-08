<?php
/**
 * Version / Release Database Repository.
 *
 * @package WPCalibrate\LicenseManager\Database\Repositories
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Database\Repositories;

use WPCalibrate\LicenseManager\Domain\PluginVersion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VersionRepository
 */
final class VersionRepository extends AbstractRepository {

	public function __construct() {
		parent::__construct( 'wpcalibrate_versions' );
	}

	public function find_by_id( int $id ): ?PluginVersion {
		$table = $this->get_table_name();
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? new PluginVersion( $row ) : null;
	}

	public function find_by_product_and_version( int $product_id, string $version ): ?PluginVersion {
		$table = $this->get_table_name();
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} WHERE product_id = %d AND version = %s LIMIT 1",
				$product_id,
				$version
			), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? new PluginVersion( $row ) : null;
	}

	public function get_latest_release( int $product_id ): ?PluginVersion {
		$table = $this->get_table_name();
		// Fetch published releases for this product ordered by release_date DESC.
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} WHERE product_id = %d AND status = 'published' ORDER BY release_date DESC, id DESC",
				$product_id
			), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return null;
		}

		// Sort semantically using version_compare to accurately find the highest version.
		usort(
			$results,
			static function ( array $a, array $b ): int {
				return version_compare( (string) $b['version'], (string) $a['version'] );
			}
		);

		return new PluginVersion( $results[0] );
	}

	public function get_by_product_id( int $product_id ): array {
		$table   = $this->get_table_name();
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} WHERE product_id = %d ORDER BY release_date DESC",
				$product_id
			), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$versions = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$versions[] = new PluginVersion( $row );
			}
		}

		return $versions;
	}

	public function get_all( array $args = array() ): array {
		$table = $this->get_table_name();

		$allowed_columns = array( 'id', 'product_id', 'version', 'status', 'release_date', 'created_at' );
		$orderby         = $this->sanitize_order_by( (string) ( $args['orderby'] ?? 'release_date' ), $allowed_columns, 'release_date' );
		$order           = $this->sanitize_order_direction( (string) ( $args['order'] ?? 'DESC' ) );
		$limit           = max( 1, (int) ( $args['limit'] ?? 20 ) );
		$offset          = max( 0, (int) ( $args['offset'] ?? 0 ) );

		$where_clauses = array( '1=1' );
		$params        = array();

		if ( ! empty( $args['product_id'] ) ) {
			$where_clauses[] = 'product_id = %d';
			$params[]        = (int) $args['product_id'];
		}

		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$params[]        = sanitize_text_field( (string) $args['status'] );
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$query     = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[]  = $limit;
		$params[]  = $offset;

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare( $query, ...$params ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		$versions = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$versions[] = new PluginVersion( $row );
			}
		}

		return $versions;
	}

	public function count_all( array $args = array() ): int {
		$table = $this->get_table_name();

		$where_clauses = array( '1=1' );
		$params        = array();

		if ( ! empty( $args['product_id'] ) ) {
			$where_clauses[] = 'product_id = %d';
			$params[]        = (int) $args['product_id'];
		}

		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$params[]        = sanitize_text_field( (string) $args['status'] );
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$query     = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

		if ( empty( $params ) ) {
			return (int) $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare( $query, ...$params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	public function insert( PluginVersion $version ): int {
		$table = $this->get_table_name();
		$data  = $version->to_array();
		unset( $data['id'] );

		$this->wpdb->insert(
			$table,
			$data,
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$insert_id = (int) $this->wpdb->insert_id;
		$version->id = $insert_id;
		return $insert_id;
	}

	public function update( PluginVersion $version ): bool {
		if ( empty( $version->id ) ) {
			return false;
		}

		$table = $this->get_table_name();
		$data  = $version->to_array();
		unset( $data['id'] );

		$result = $this->wpdb->update(
			$table,
			$data,
			array( 'id' => $version->id ),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	public function delete( int $id ): bool {
		$table  = $this->get_table_name();
		$result = $this->wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
		return false !== $result;
	}
}
