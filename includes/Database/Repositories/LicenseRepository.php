<?php
/**
 * License Database Repository.
 *
 * @package WPCalibrate\LicenseManager\Database\Repositories
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Database\Repositories;

use WPCalibrate\LicenseManager\Domain\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LicenseRepository
 */
final class LicenseRepository extends AbstractRepository {

	public function __construct() {
		parent::__construct( 'wpcalibrate_licenses' );
	}

	public function find_by_id( int $id ): ?License {
		$table = $this->get_table_name();
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? new License( $row ) : null;
	}

	public function find_by_hash( string $hash ): ?License {
		$table = $this->get_table_name();
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE license_key_hash = %s LIMIT 1", $hash ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? new License( $row ) : null;
	}

	public function find_by_order_id( int $order_id, int $product_id ): ?License {
		$table = $this->get_table_name();
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d AND product_id = %d LIMIT 1", $order_id, $product_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? new License( $row ) : null;
	}

	public function get_all( array $args = array() ): array {
		$table = $this->get_table_name();

		$allowed_columns = array( 'id', 'product_id', 'status', 'expires_at', 'created_at', 'activation_count' );
		$orderby         = $this->sanitize_order_by( (string) ( $args['orderby'] ?? 'id' ), $allowed_columns, 'id' );
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

		if ( ! empty( $args['customer_email'] ) ) {
			$where_clauses[] = 'customer_email = %s';
			$params[]        = sanitize_email( (string) $args['customer_email'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(license_key_masked LIKE %s OR customer_email LIKE %s OR admin_notes LIKE %s)';
			$like            = '%' . $this->wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%';
			$params[]        = $like;
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

		$licenses = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$licenses[] = new License( $row );
			}
		}

		return $licenses;
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

		if ( ! empty( $args['customer_email'] ) ) {
			$where_clauses[] = 'customer_email = %s';
			$params[]        = sanitize_email( (string) $args['customer_email'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(license_key_masked LIKE %s OR customer_email LIKE %s OR admin_notes LIKE %s)';
			$like            = '%' . $this->wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%';
			$params[]        = $like;
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

	public function insert( License $license ): int {
		$table = $this->get_table_name();
		$data  = $license->to_array();
		unset( $data['id'] );

		$this->wpdb->insert(
			$table,
			$data,
			array(
				'%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d',
				'%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s'
			)
		);

		$insert_id = (int) $this->wpdb->insert_id;
		$license->id = $insert_id;
		return $insert_id;
	}

	public function update( License $license ): bool {
		if ( empty( $license->id ) ) {
			return false;
		}

		$table               = $this->get_table_name();
		$data                = $license->to_array();
		$data['updated_at']  = current_time( 'mysql', true );
		$license->updated_at = $data['updated_at'];
		unset( $data['id'] );

		$result = $this->wpdb->update(
			$table,
			$data,
			array( 'id' => $license->id ),
			array(
				'%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d',
				'%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s'
			),
			array( '%d' )
		);

		return false !== $result;
	}

	public function update_status( int $id, string $status ): bool {
		$table  = $this->get_table_name();
		$result = $this->wpdb->update(
			$table,
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	public function recalculate_activation_count( int $license_id ): bool {
		$table_licenses    = $this->get_table_name();
		$table_activations = ( new ActivationRepository() )->get_table_name();

		$count = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_activations} WHERE license_id = %d AND is_staging = 0",
				$license_id
			) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$result = $this->wpdb->update(
			$table_licenses,
			array(
				'activation_count' => $count,
				'updated_at'       => current_time( 'mysql', true ),
			),
			array( 'id' => $license_id ),
			array( '%d', '%s' ),
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
