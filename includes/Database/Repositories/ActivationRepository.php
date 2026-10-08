<?php
/**
 * Activation Database Repository.
 *
 * @package WPCalibrate\LicenseManager\Database\Repositories
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Database\Repositories;

use WPCalibrate\LicenseManager\Domain\Activation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ActivationRepository
 */
final class ActivationRepository extends AbstractRepository {

	public function __construct() {
		parent::__construct( 'wpcalibrate_activations' );
	}

	public function find_by_id( int $id ): ?Activation {
		$table = $this->get_table_name();
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? new Activation( $row ) : null;
	}

	public function find_by_license_and_site( int $license_id, string $site_identifier ): ?Activation {
		$table = $this->get_table_name();
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} WHERE license_id = %d AND site_identifier = %s LIMIT 1",
				$license_id,
				$site_identifier
			), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? new Activation( $row ) : null;
	}

	public function get_by_license_id( int $license_id ): array {
		$table   = $this->get_table_name();
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare( "SELECT * FROM {$table} WHERE license_id = %d ORDER BY created_at DESC", $license_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$activations = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$activations[] = new Activation( $row );
			}
		}

		return $activations;
	}

	public function count_production_activations( int $license_id ): int {
		$table = $this->get_table_name();
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE license_id = %d AND is_staging = 0",
				$license_id
			) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public function get_all( array $args = array() ): array {
		$table = $this->get_table_name();

		$allowed_columns = array( 'id', 'license_id', 'product_id', 'site_domain', 'created_at', 'last_validated_at' );
		$orderby         = $this->sanitize_order_by( (string) ( $args['orderby'] ?? 'id' ), $allowed_columns, 'id' );
		$order           = $this->sanitize_order_direction( (string) ( $args['order'] ?? 'DESC' ) );
		$limit           = max( 1, (int) ( $args['limit'] ?? 20 ) );
		$offset          = max( 0, (int) ( $args['offset'] ?? 0 ) );

		$where_clauses = array( '1=1' );
		$params        = array();

		if ( ! empty( $args['license_id'] ) ) {
			$where_clauses[] = 'license_id = %d';
			$params[]        = (int) $args['license_id'];
		}

		if ( ! empty( $args['product_id'] ) ) {
			$where_clauses[] = 'product_id = %d';
			$params[]        = (int) $args['product_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(site_domain LIKE %s OR site_url LIKE %s OR site_ip LIKE %s)';
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

		$activations = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$activations[] = new Activation( $row );
			}
		}

		return $activations;
	}

	public function count_all( array $args = array() ): int {
		$table = $this->get_table_name();

		$where_clauses = array( '1=1' );
		$params        = array();

		if ( ! empty( $args['license_id'] ) ) {
			$where_clauses[] = 'license_id = %d';
			$params[]        = (int) $args['license_id'];
		}

		if ( ! empty( $args['product_id'] ) ) {
			$where_clauses[] = 'product_id = %d';
			$params[]        = (int) $args['product_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(site_domain LIKE %s OR site_url LIKE %s OR site_ip LIKE %s)';
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

	public function insert( Activation $activation ): int {
		$table = $this->get_table_name();
		$data  = $activation->to_array();
		unset( $data['id'] );

		$this->wpdb->insert(
			$table,
			$data,
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
		);

		$insert_id = (int) $this->wpdb->insert_id;
		$activation->id = $insert_id;
		return $insert_id;
	}

	public function update( Activation $activation ): bool {
		if ( empty( $activation->id ) ) {
			return false;
		}

		$table                  = $this->get_table_name();
		$data                   = $activation->to_array();
		$data['updated_at']     = current_time( 'mysql', true );
		$activation->updated_at = $data['updated_at'];
		unset( $data['id'] );

		$result = $this->wpdb->update(
			$table,
			$data,
			array( 'id' => $activation->id ),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	public function delete( int $id ): bool {
		$table  = $this->get_table_name();
		$result = $this->wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
		return false !== $result;
	}

	public function delete_by_license_and_site( int $license_id, string $site_identifier ): bool {
		$table  = $this->get_table_name();
		$result = $this->wpdb->delete(
			$table,
			array(
				'license_id'      => $license_id,
				'site_identifier' => $site_identifier,
			),
			array( '%d', '%s' )
		);

		return false !== $result;
	}

	public function delete_all_by_license_id( int $license_id ): int {
		$table  = $this->get_table_name();
		$result = $this->wpdb->delete( $table, array( 'license_id' => $license_id ), array( '%d' ) );
		return false === $result ? 0 : (int) $result;
	}
}
