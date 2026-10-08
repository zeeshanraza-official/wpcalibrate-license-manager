<?php
/**
 * Audit Event Database Repository.
 *
 * @package WPCalibrate\LicenseManager\Database\Repositories
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Database\Repositories;

use WPCalibrate\LicenseManager\Domain\AuditEvent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EventRepository
 */
final class EventRepository extends AbstractRepository {

	public function __construct() {
		parent::__construct( 'wpcalibrate_license_events' );
	}

	public function insert( AuditEvent $event ): int {
		$table = $this->get_table_name();
		$data  = $event->to_array();
		unset( $data['id'] );

		$this->wpdb->insert(
			$table,
			$data,
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		$insert_id = (int) $this->wpdb->insert_id;
		$event->id = $insert_id;
		return $insert_id;
	}

	public function get_recent( array $args = array() ): array {
		$table  = $this->get_table_name();
		$limit  = max( 1, (int) ( $args['limit'] ?? 20 ) );
		$offset = max( 0, (int) ( $args['offset'] ?? 0 ) );

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

		if ( ! empty( $args['event_type'] ) ) {
			$where_clauses[] = 'event_type = %s';
			$params[]        = sanitize_text_field( (string) $args['event_type'] );
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$query     = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$params[]  = $limit;
		$params[]  = $offset;

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare( $query, ...$params ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		$events = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$events[] = new AuditEvent( $row );
			}
		}

		return $events;
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

		$where_sql = implode( ' AND ', $where_clauses );
		$query     = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

		if ( empty( $params ) ) {
			return (int) $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare( $query, ...$params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	public function prune_older_than( int $days ): int {
		if ( $days <= 0 ) {
			return 0;
		}

		$table  = $this->get_table_name();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$result = $this->wpdb->query(
			$this->wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return false === $result ? 0 : (int) $result;
	}
}
