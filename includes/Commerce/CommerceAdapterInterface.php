<?php
/**
 * Commerce Adapter Interface.
 *
 * @package WPCalibrate\LicenseManager\Commerce
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Commerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface CommerceAdapterInterface
 */
interface CommerceAdapterInterface {

	/**
	 * Register commerce hooks and listeners.
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Handle order completion.
	 *
	 * @param int $order_id
	 * @return void
	 */
	public function on_order_completed( int $order_id ): void;

	/**
	 * Handle order refund or cancellation.
	 *
	 * @param int $order_id
	 * @return void
	 */
	public function on_order_refunded( int $order_id ): void;
}
