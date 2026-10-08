<?php
/**
 * Product Database Repository.
 *
 * @package WPCalibrate\LicenseManager\Database\Repositories
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Database\Repositories;

use WPCalibrate\LicenseManager\Domain\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ProductRepository
 */
final class ProductRepository extends AbstractRepository {

	public function __construct() {
		parent::__construct( 'wpcalibrate_products' );
	}

	public function find_by_id( int $id ): ?Product {
		$table = $this->get_table_name();
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? new Product( $row ) : null;
	}

	public function find_by_slug( string $slug ): ?Product {
		$table = $this->get_table_name();
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s LIMIT 1", $slug ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? new Product( $row ) : null;
	}

	public function find_by_wc_product_id( int $wc_product_id ): ?Product {
		$table = $this->get_table_name();
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE wc_product_id = %d LIMIT 1", $wc_product_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? new Product( $row ) : null;
	}

	public function get_all( array $args = array() ): array {
		$table = $this->get_table_name();

		$allowed_columns = array( 'id', 'name', 'slug', 'current_version', 'status', 'created_at' );
		$orderby         = $this->sanitize_order_by( (string) ( $args['orderby'] ?? 'id' ), $allowed_columns, 'id' );
		$order           = $this->sanitize_order_direction( (string) ( $args['order'] ?? 'DESC' ) );
		$limit           = max( 1, (int) ( $args['limit'] ?? 20 ) );
		$offset          = max( 0, (int) ( $args['offset'] ?? 0 ) );

		$where_clauses = array( '1=1' );
		$params        = array();

		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$params[]        = sanitize_text_field( (string) $args['status'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(name LIKE %s OR slug LIKE %s)';
			$like            = '%' . $this->wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%';
			$params[]        = $like;
			$params[]        = $like;
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$query     = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[]  = $limit;
		$params[]  = $offset;

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare( $query, ...$params ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		$products = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$products[] = new Product( $row );
			}
		}

		return $products;
	}

	public function count_all( array $args = array() ): int {
		$table = $this->get_table_name();

		$where_clauses = array( '1=1' );
		$params        = array();

		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$params[]        = sanitize_text_field( (string) $args['status'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(name LIKE %s OR slug LIKE %s)';
			$like            = '%' . $this->wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%';
			$params[]        = $like;
			$params[]        = $like;
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

	public function insert( Product $product ): int {
		$table = $this->get_table_name();
		$data  = $product->to_array();
		unset( $data['id'] );

		$this->wpdb->insert(
			$table,
			$data,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		$insert_id = (int) $this->wpdb->insert_id;
		$product->id = $insert_id;
		return $insert_id;
	}

	public function update( Product $product ): bool {
		if ( empty( $product->id ) ) {
			return false;
		}

		$table               = $this->get_table_name();
		$data                = $product->to_array();
		$data['updated_at']  = current_time( 'mysql', true );
		$product->updated_at = $data['updated_at'];
		unset( $data['id'] );

		$result = $this->wpdb->update(
			$table,
			$data,
			array( 'id' => $product->id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ),
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
