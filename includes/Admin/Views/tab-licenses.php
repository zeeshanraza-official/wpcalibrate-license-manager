<?php
/**
 * Admin Tab: Licenses.
 *
 * @package WPCalibrate\LicenseManager\Admin\Views
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_num       = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$limit          = 20;
$offset         = ( $page_num - 1 ) * $limit;
$search         = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$status_filter  = sanitize_key( $_GET['status_filter'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$product_filter = ! empty( $_GET['product_filter'] ) ? (int) $_GET['product_filter'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$args = array(
	'search'     => $search,
	'limit'      => $limit,
	'offset'     => $offset,
	'status'     => $status_filter,
	'product_id' => $product_filter,
);

$licenses       = $this->license_repo->get_all( $args );
$total_licenses = $this->license_repo->count_all( $args );
$all_products   = $this->product_repo->get_all( array( 'limit' => 200 ) );

// Fetch products map for quick lookup.
$product_map = array();
foreach ( $all_products as $p ) {
	$product_map[ $p->id ] = $p->name;
}
?>
<div class="wpcalibrate-two-col">
	<div class="wpcalibrate-col-list">
		<div class="tablenav top">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="alignleft">
				<input type="hidden" name="page" value="wpcalibrate-license-manager" />
				<input type="hidden" name="tab" value="licenses" />

				<select name="status_filter">
					<option value=""><?php esc_html_e( 'All Statuses', 'wpcalibrate-license-manager' ); ?></option>
					<option value="active" <?php selected( $status_filter, 'active' ); ?>><?php esc_html_e( 'Active', 'wpcalibrate-license-manager' ); ?></option>
					<option value="expired" <?php selected( $status_filter, 'expired' ); ?>><?php esc_html_e( 'Expired', 'wpcalibrate-license-manager' ); ?></option>
					<option value="suspended" <?php selected( $status_filter, 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'wpcalibrate-license-manager' ); ?></option>
					<option value="revoked" <?php selected( $status_filter, 'revoked' ); ?>><?php esc_html_e( 'Revoked', 'wpcalibrate-license-manager' ); ?></option>
				</select>

				<select name="product_filter">
					<option value=""><?php esc_html_e( 'All Products', 'wpcalibrate-license-manager' ); ?></option>
					<?php foreach ( $all_products as $p ) : ?>
						<option value="<?php echo esc_attr( (string) $p->id ); ?>" <?php selected( $product_filter, $p->id ); ?>><?php echo esc_html( $p->name ); ?></option>
					<?php endforeach; ?>
				</select>

				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search key / email...', 'wpcalibrate-license-manager' ); ?>" />
				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wpcalibrate-license-manager' ); ?>" />
			</form>
			<div class="tablenav-pages alignright">
				<span class="displaying-num"><?php printf( esc_html__( '%d items', 'wpcalibrate-license-manager' ), (int) $total_licenses ); ?></span>
			</div>
		</div>

		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'License Key (Masked)', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Product', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Customer', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Seats (Used / Max)', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Expires', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wpcalibrate-license-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $licenses ) ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'No licenses found.', 'wpcalibrate-license-manager' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $licenses as $lic ) : ?>
						<tr>
							<td><code><?php echo esc_html( $lic->license_key_masked ); ?></code></td>
							<td><?php echo esc_html( $product_map[ $lic->product_id ] ?? 'Product #' . $lic->product_id ); ?></td>
							<td><?php echo esc_html( $lic->customer_email ?: '-' ); ?></td>
							<td>
								<strong><?php echo esc_html( (string) $lic->activation_count ); ?></strong> /
								<?php echo 0 === $lic->activation_limit ? esc_html__( 'Unlimited', 'wpcalibrate-license-manager' ) : esc_html( (string) $lic->activation_limit ); ?>
							</td>
							<td>
								<?php if ( $lic->is_lifetime ) : ?>
									<span class="wpcalibrate-badge-lifetime"><?php esc_html_e( 'Lifetime', 'wpcalibrate-license-manager' ); ?></span>
								<?php else : ?>
									<?php echo esc_html( substr( (string) $lic->expires_at, 0, 10 ) ); ?>
								<?php endif; ?>
							</td>
							<td><span class="wpcalibrate-status-badge status-<?php echo esc_attr( $lic->status ); ?>"><?php echo esc_html( ucfirst( $lic->status ) ); ?></span></td>
							<td>
								<div class="wpcalibrate-action-group">
									<?php if ( 'active' === $lic->status ) : ?>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
											<input type="hidden" name="wpcalibrate_lm_action" value="update_license_status" />
											<input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $lic->id ); ?>" />
											<input type="hidden" name="status" value="suspended" />
											<input type="submit" class="button button-small" value="<?php esc_attr_e( 'Suspend', 'wpcalibrate-license-manager' ); ?>" />
										</form>
										<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Revoke this license? Future activations and updates will be blocked.', 'wpcalibrate-license-manager' ); ?>');">
											<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
											<input type="hidden" name="wpcalibrate_lm_action" value="update_license_status" />
											<input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $lic->id ); ?>" />
											<input type="hidden" name="status" value="revoked" />
											<input type="submit" class="button button-small button-link-delete" value="<?php esc_attr_e( 'Revoke', 'wpcalibrate-license-manager' ); ?>" />
										</form>
									<?php else : ?>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
											<input type="hidden" name="wpcalibrate_lm_action" value="update_license_status" />
											<input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $lic->id ); ?>" />
											<input type="hidden" name="status" value="active" />
											<input type="submit" class="button button-small button-primary" value="<?php esc_attr_e( 'Restore', 'wpcalibrate-license-manager' ); ?>" />
										</form>
									<?php endif; ?>

									<?php if ( ! $lic->is_lifetime ) : ?>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
											<input type="hidden" name="wpcalibrate_lm_action" value="extend_license" />
											<input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $lic->id ); ?>" />
											<input type="hidden" name="days" value="365" />
											<input type="submit" class="button button-small" value="+1y" title="<?php esc_attr_e( 'Extend 1 Year', 'wpcalibrate-license-manager' ); ?>" />
										</form>
									<?php endif; ?>

									<?php if ( $lic->activation_count > 0 ) : ?>
										<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Reset all activations for this license?', 'wpcalibrate-license-manager' ); ?>');">
											<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
											<input type="hidden" name="wpcalibrate_lm_action" value="reset_activations" />
											<input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $lic->id ); ?>" />
											<input type="submit" class="button button-small" value="<?php esc_attr_e( 'Reset Seats', 'wpcalibrate-license-manager' ); ?>" />
										</form>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="wpcalibrate-col-form">
		<div class="wpcalibrate-card">
			<h2><?php esc_html_e( 'Generate New License', 'wpcalibrate-license-manager' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
				<input type="hidden" name="wpcalibrate_lm_action" value="create_license" />

				<p>
					<label for="lic_prod"><strong><?php esc_html_e( 'Target Product', 'wpcalibrate-license-manager' ); ?> *</strong></label>
					<select id="lic_prod" name="product_id" class="widefat" required>
						<option value=""><?php esc_html_e( 'Select Product...', 'wpcalibrate-license-manager' ); ?></option>
						<?php foreach ( $all_products as $p ) : ?>
							<option value="<?php echo esc_attr( (string) $p->id ); ?>"><?php echo esc_html( $p->name ); ?> (<?php echo esc_html( $p->slug ); ?>)</option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<label for="lic_email"><strong><?php esc_html_e( 'Customer Email', 'wpcalibrate-license-manager' ); ?></strong></label>
					<input type="email" id="lic_email" name="customer_email" class="widefat" placeholder="customer@example.com" />
				</p>

				<p>
					<label for="lic_limit"><strong><?php esc_html_e( 'Activation Seat Limit', 'wpcalibrate-license-manager' ); ?></strong></label>
					<input type="number" id="lic_limit" name="activation_limit" class="widefat" value="1" min="0" />
					<span class="description"><?php esc_html_e( 'Set to 0 for unlimited activation seats.', 'wpcalibrate-license-manager' ); ?></span>
				</p>

				<p>
					<label>
						<input type="checkbox" name="is_lifetime" value="1" id="lic_is_lifetime" />
						<strong><?php esc_html_e( 'Lifetime License (Never Expires)', 'wpcalibrate-license-manager' ); ?></strong>
					</label>
				</p>

				<p id="lic_expiry_field">
					<label for="lic_expires_at"><strong><?php esc_html_e( 'Custom Expiry Date (YYYY-MM-DD)', 'wpcalibrate-license-manager' ); ?></strong></label>
					<input type="date" id="lic_expires_at" name="expires_at" class="widefat" />
					<span class="description"><?php esc_html_e( 'Leave empty for default 1-year duration.', 'wpcalibrate-license-manager' ); ?></span>
				</p>

				<p>
					<label for="lic_notes"><strong><?php esc_html_e( 'Admin Notes', 'wpcalibrate-license-manager' ); ?></strong></label>
					<textarea id="lic_notes" name="admin_notes" class="widefat" rows="2" placeholder="Internal memo..."></textarea>
				</p>

				<p>
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Generate Cryptographic Key', 'wpcalibrate-license-manager' ); ?>" />
				</p>
			</form>
		</div>
	</div>
</div>
