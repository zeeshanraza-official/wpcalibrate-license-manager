<?php
/**
 * Admin Tab: Products.
 *
 * @package WPCalibrate\LicenseManager\Admin\Views
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_num  = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$limit     = 20;
$offset    = ( $page_num - 1 ) * $limit;
$search    = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$products       = $this->product_repo->get_all( array( 'search' => $search, 'limit' => $limit, 'offset' => $offset ) );
$total_products = $this->product_repo->count_all( array( 'search' => $search ) );
$total_pages    = (int) ceil( $total_products / $limit );
?>
<div class="wpcalibrate-two-col">
	<div class="wpcalibrate-col-list">
		<div class="tablenav top">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="alignleft">
				<input type="hidden" name="page" value="wpcalibrate-license-manager" />
				<input type="hidden" name="tab" value="products" />
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search products...', 'wpcalibrate-license-manager' ); ?>" />
				<input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'wpcalibrate-license-manager' ); ?>" />
			</form>
			<div class="tablenav-pages alignright">
				<span class="displaying-num"><?php printf( esc_html__( '%d items', 'wpcalibrate-license-manager' ), (int) $total_products ); ?></span>
			</div>
		</div>

		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Name', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Slug', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Version', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'WC Mapped ID', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wpcalibrate-license-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $products ) ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'No products found.', 'wpcalibrate-license-manager' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $products as $prod ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $prod->id ); ?></td>
							<td><strong><?php echo esc_html( $prod->name ); ?></strong></td>
							<td><code><?php echo esc_html( $prod->slug ); ?></code></td>
							<td><?php echo esc_html( $prod->current_version ); ?></td>
							<td><?php echo esc_html( $prod->wc_product_id ? (string) $prod->wc_product_id : '-' ); ?></td>
							<td><span class="wpcalibrate-status-badge status-<?php echo esc_attr( $prod->status ); ?>"><?php echo esc_html( ucfirst( $prod->status ) ); ?></span></td>
							<td>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this product?', 'wpcalibrate-license-manager' ); ?>');">
									<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
									<input type="hidden" name="wpcalibrate_lm_action" value="delete_product" />
									<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $prod->id ); ?>" />
									<input type="submit" class="button button-small button-link-delete" value="<?php esc_attr_e( 'Delete', 'wpcalibrate-license-manager' ); ?>" />
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="wpcalibrate-col-form">
		<div class="wpcalibrate-card">
			<h2><?php esc_html_e( 'Add / Update Product', 'wpcalibrate-license-manager' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
				<input type="hidden" name="wpcalibrate_lm_action" value="save_product" />

				<p>
					<label for="prod_name"><strong><?php esc_html_e( 'Product Name', 'wpcalibrate-license-manager' ); ?> *</strong></label>
					<input type="text" id="prod_name" name="name" class="widefat" required placeholder="e.g. WPCalibrate Pro" />
				</p>

				<p>
					<label for="prod_slug"><strong><?php esc_html_e( 'Plugin Slug', 'wpcalibrate-license-manager' ); ?> *</strong></label>
					<input type="text" id="prod_slug" name="slug" class="widefat" required placeholder="e.g. wpcalibrate-pro" />
					<span class="description"><?php esc_html_e( 'Must match the host plugin folder and update slug.', 'wpcalibrate-license-manager' ); ?></span>
				</p>

				<p>
					<label for="prod_ver"><strong><?php esc_html_e( 'Current Stable Version', 'wpcalibrate-license-manager' ); ?></strong></label>
					<input type="text" id="prod_ver" name="current_version" class="widefat" value="1.0.0" />
				</p>

				<p>
					<label for="prod_wc_id"><strong><?php esc_html_e( 'WooCommerce Product ID (Optional)', 'wpcalibrate-license-manager' ); ?></strong></label>
					<input type="number" id="prod_wc_id" name="wc_product_id" class="widefat" placeholder="e.g. 105" />
					<span class="description"><?php esc_html_e( 'Automatically issues licenses when this WooCommerce item is purchased.', 'wpcalibrate-license-manager' ); ?></span>
				</p>

				<p>
					<label for="prod_renewal"><strong><?php esc_html_e( 'Renewal URL', 'wpcalibrate-license-manager' ); ?></strong></label>
					<input type="url" id="prod_renewal" name="renewal_url" class="widefat" placeholder="https://marketplace.wpcalibrate.com/checkout?renew=..." />
				</p>

				<p>
					<label for="prod_changelog"><strong><?php esc_html_e( 'Changelog', 'wpcalibrate-license-manager' ); ?></strong></label>
					<textarea id="prod_changelog" name="changelog" class="widefat" rows="4" placeholder="= 1.0.0 =&#10;* Initial release."></textarea>
				</p>

				<p>
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Product', 'wpcalibrate-license-manager' ); ?>" />
				</p>
			</form>
		</div>
	</div>
</div>
