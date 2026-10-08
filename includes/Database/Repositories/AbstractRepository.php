<?php
/**
 * Abstract Database Repository.
 *
 * @package WPCalibrate\LicenseManager\Database\Repositories
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Database\Repositories;

use WPCalibrate\LicenseManager\Database\Schema;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AbstractRepository
 */
abstract class AbstractRepository {

	/**
	 * WordPress DB instance.
	 *
	 * @var wpdb
	 */
	protected wpdb $wpdb;

	/**
	 * Raw table name without prefix.
	 *
	 * @var string
	 */
	protected string $raw_table_name;

	/**
	 * Constructor.
	 *
	 * @param string $raw_table_name Table name without prefix.
	 */
	public function __construct( string $raw_table_name ) {
		global $wpdb;
		$this->wpdb           = $wpdb;
		$this->raw_table_name = $raw_table_name;
	}

	/**
	 * Get the prefixed table name.
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		return Schema::get_table_name( $this->raw_table_name );
	}

	/**
	 * Allowlist an order by column.
	 *
	 * @param string        $column           Input column name.
	 * @param array<string> $allowed_columns  Allowlisted column names.
	 * @param string        $default_column   Fallback column name.
	 * @return string
	 */
	protected function sanitize_order_by( string $column, array $allowed_columns, string $default_column = 'id' ): string {
		return in_array( $column, $allowed_columns, true ) ? $column : $default_column;
	}

	/**
	 * Allowlist order direction.
	 *
	 * @param string $order Input direction.
	 * @return string 'ASC' or 'DESC'.
	 */
	protected function sanitize_order_direction( string $order ): string {
		return 'ASC' === strtoupper( trim( $order ) ) ? 'ASC' : 'DESC';
	}
}
