<?php
/**
 * WooCommerce Commerce Adapter.
 *
 * Fully HPOS compatible using WooCommerce CRUD APIs.
 *
 * @package WPCalibrate\LicenseManager\Commerce
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Commerce;

use WPCalibrate\LicenseManager\Database\Repositories\ProductRepository;
use WPCalibrate\LicenseManager\Licensing\LicenseService;
use WPCalibrate\LicenseManager\Logging\AuditLogger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WooCommerceAdapter
 */
final class WooCommerceAdapter implements CommerceAdapterInterface {

	private LicenseService $license_service;
	private ProductRepository $product_repo;
	private AuditLogger $logger;

	public function __construct(
		LicenseService $license_service,
		ProductRepository $product_repo,
		AuditLogger $logger
	) {
		$this->license_service = $license_service;
		$this->product_repo    = $product_repo;
		$this->logger          = $logger;
	}

	public function init(): void {
		$settings = get_option( 'wpcalibrate_lm_settings', array() );
		if ( empty( $settings['wc_auto_license'] ) ) {
			return;
		}

		// Hook into standard order completion and virtual order processing.
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ), 10, 1 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_order_completed' ), 10, 1 );

		// Hook into order refunded or cancelled.
		add_action( 'woocommerce_order_status_refunded', array( $this, 'on_order_refunded' ), 10, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'on_order_refunded' ), 10, 1 );

		// Display license details on WooCommerce thank you and view order screens.
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_order_license_details' ), 10, 1 );
	}

	/**
	 * Handle order completion: idempotently generate licenses for mapped products.
	 *
	 * @param int $order_id
	 */
	public function on_order_completed( int $order_id ): void {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Idempotency check on order level: prevent duplicate generation if hooks fire repeatedly.
		$already_processed = (bool) $order->get_meta( '_wpcalibrate_lm_license_created' );
		if ( $already_processed ) {
			return;
		}

		$generated_any = false;
		$items         = $order->get_items();

		foreach ( $items as $item_id => $item ) {
			$product_id = (int) $item->get_product_id();
			$variation_id = (int) $item->get_variation_id();

			// Look up mapping by variation or parent product ID.
			$cal_product = null;
			if ( $variation_id > 0 ) {
				$cal_product = $this->product_repo->find_by_wc_product_id( $variation_id );
			}
			if ( ! $cal_product && $product_id > 0 ) {
				$cal_product = $this->product_repo->find_by_wc_product_id( $product_id );
			}

			if ( ! $cal_product ) {
				continue;
			}

			// Check if item already has a license key attached.
			$existing_key = (string) $item->get_meta( '_wpcalibrate_license_key' );
			if ( ! empty( $existing_key ) ) {
				continue;
			}

			// Quantity support: generate license per purchased item quantity.
			$quantity = max( 1, (int) $item->get_quantity() );
			for ( $i = 0; $i < $quantity; $i++ ) {
				$created = $this->license_service->create_license(
					array(
						'product_id'     => $cal_product->id,
						'customer_id'    => $order->get_customer_id() ?: null,
						'customer_email' => $order->get_billing_email(),
						'order_id'       => $order->get_id(),
						'admin_notes'    => sprintf( 'Generated automatically from WC Order #%d', $order->get_id() ),
					)
				);

				$license       = $created['license'];
				$plaintext_key = $created['plaintext_key'];

				// Store plaintext in item meta for customer presentation.
				$item->add_meta_data( '_wpcalibrate_license_key', $plaintext_key, false );
				$item->add_meta_data( '_wpcalibrate_license_id', $license->id, false );
				$item->save();

				// Add order note (masked for security).
				$order->add_order_note(
					sprintf(
						/* translators: 1: Product name, 2: Masked license key */
						__( 'WPCalibrate License generated for %1$s: %2$s', 'wpcalibrate-license-manager' ),
						$cal_product->name,
						$license->license_key_masked
					)
				);

				$generated_any = true;
			}
		}

		if ( $generated_any ) {
			$order->update_meta_data( '_wpcalibrate_lm_license_created', 'yes' );
			$order->save();
		}
	}

	/**
	 * Handle order refund or cancellation.
	 *
	 * @param int $order_id
	 */
	public function on_order_refunded( int $order_id ): void {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$settings = get_option( 'wpcalibrate_lm_settings', array() );
		$action   = (string) ( $settings['wc_refund_action'] ?? 'suspend' );

		if ( 'none' === $action ) {
			return;
		}

		$items = $order->get_items();
		foreach ( $items as $item ) {
			$license_id = (int) $item->get_meta( '_wpcalibrate_license_id' );
			if ( $license_id > 0 ) {
				$new_status = 'revoke' === $action ? 'revoked' : 'suspended';
				$this->license_service->change_status(
					$license_id,
					$new_status,
					sprintf( 'Order #%d %s', $order_id, $order->get_status() )
				);
			}
		}
	}

	/**
	 * Display customer license keys on Order Received page.
	 *
	 * @param \WC_Order $order
	 */
	public function display_order_license_details( $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$licenses = array();
		foreach ( $order->get_items() as $item ) {
			$key = (string) $item->get_meta( '_wpcalibrate_license_key' );
			if ( ! empty( $key ) ) {
				$licenses[] = array(
					'product' => $item->get_name(),
					'key'     => $key,
				);
			}
		}

		if ( empty( $licenses ) ) {
			return;
		}

		echo '<section class="wpcalibrate-order-licenses" style="margin-top:20px;padding:15px;background:#f9f9f9;border-left:4px solid #2271b1;">';
		echo '<h3 style="margin-top:0;">' . esc_html__( 'Your WPCalibrate License Keys', 'wpcalibrate-license-manager' ) . '</h3>';
		echo '<table class="woocommerce-table" style="width:100%;">';
		echo '<thead><tr><th>' . esc_html__( 'Product', 'wpcalibrate-license-manager' ) . '</th><th>' . esc_html__( 'License Key', 'wpcalibrate-license-manager' ) . '</th></tr></thead><tbody>';

		foreach ( $licenses as $lic ) {
			echo '<tr>';
			echo '<td><strong>' . esc_html( $lic['product'] ) . '</strong></td>';
			echo '<td><code style="background:#fff;padding:4px 8px;border:1px solid #ddd;font-size:1.1em;user-select:all;">' . esc_html( $lic['key'] ) . '</code></td>';
			echo '</tr>';
		}

		echo '</tbody></table></section>';
	}
}
