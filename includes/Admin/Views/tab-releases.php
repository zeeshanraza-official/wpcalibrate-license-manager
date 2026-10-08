<?php
/**
 * Admin Tab: Releases & Version Management.
 *
 * @package WPCalibrate\LicenseManager\Admin\Views
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$all_products = $this->product_repo->get_all( array( 'limit' => 200 ) );
$releases     = $this->version_repo->get_all( array( 'limit' => 50 ) );

$product_map = array();
foreach ( $all_products as $p ) {
	$product_map[ $p->id ] = $p->name;
}
?>
<div class="wpcalibrate-two-col">
	<div class="wpcalibrate-col-list">
		<h2><?php esc_html_e( 'Registered Plugin Releases', 'wpcalibrate-license-manager' ); ?></h2>
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Product', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Version', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Requirements', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Package Size', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Released', 'wpcalibrate-license-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $releases ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No releases uploaded yet.', 'wpcalibrate-license-manager' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $releases as $rel ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $product_map[ $rel->product_id ] ?? 'Product #' . $rel->product_id ); ?></strong></td>
							<td><code>v<?php echo esc_html( $rel->version ); ?></code></td>
							<td>
								<small>
									WP: <?php echo esc_html( $rel->min_wp_version ); ?>+ (Tested: <?php echo esc_html( $rel->tested_wp_version ); ?>)<br>
									PHP: <?php echo esc_html( $rel->min_php_version ); ?>+
								</small>
							</td>
							<td><?php echo esc_html( size_format( (int) $rel->package_size ) ); ?></td>
							<td><span class="wpcalibrate-status-badge status-<?php echo esc_attr( $rel->status ); ?>"><?php echo esc_html( ucfirst( $rel->status ) ); ?></span></td>
							<td><?php echo esc_html( substr( $rel->release_date, 0, 10 ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="wpcalibrate-col-form">
		<div class="wpcalibrate-card">
			<h2><?php esc_html_e( 'Upload / Register New Release', 'wpcalibrate-license-manager' ); ?></h2>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
				<input type="hidden" name="wpcalibrate_lm_action" value="save_release" />

				<p>
					<label for="rel_prod"><strong><?php esc_html_e( 'Select Product', 'wpcalibrate-license-manager' ); ?> *</strong></label>
					<select id="rel_prod" name="product_id" class="widefat" required>
						<option value=""><?php esc_html_e( 'Select Product...', 'wpcalibrate-license-manager' ); ?></option>
						<?php foreach ( $all_products as $p ) : ?>
							<option value="<?php echo esc_attr( (string) $p->id ); ?>"><?php echo esc_html( $p->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<label for="rel_version"><strong><?php esc_html_e( 'Release Version', 'wpcalibrate-license-manager' ); ?> *</strong></label>
					<input type="text" id="rel_version" name="version" class="widefat" required placeholder="e.g. 1.1.0" />
				</p>

				<p>
					<label for="rel_zip"><strong><?php esc_html_e( 'Plugin ZIP Archive', 'wpcalibrate-license-manager' ); ?></strong></label>
					<input type="file" id="rel_zip" name="package_zip" accept=".zip" class="widefat" />
					<span class="description"><?php esc_html_e( 'Protected storage; files are never executed on server.', 'wpcalibrate-license-manager' ); ?></span>
				</p>

				<p>
					<label for="rel_path"><strong><?php esc_html_e( 'Or Existing Server File Path', 'wpcalibrate-license-manager' ); ?></strong></label>
					<input type="text" id="rel_path" name="existing_package_path" class="widefat" placeholder="/path/to/plugin-v1.1.0.zip" />
				</p>

				<div style="display:flex;gap:10px;">
					<p style="flex:1;">
						<label for="rel_min_wp"><strong><?php esc_html_e( 'Min WP', 'wpcalibrate-license-manager' ); ?></strong></label>
						<input type="text" id="rel_min_wp" name="min_wp_version" class="widefat" value="6.5" />
					</p>
					<p style="flex:1;">
						<label for="rel_tested_wp"><strong><?php esc_html_e( 'Tested Up To WP', 'wpcalibrate-license-manager' ); ?></strong></label>
						<input type="text" id="rel_tested_wp" name="tested_wp_version" class="widefat" value="7.1.3" />
					</p>
					<p style="flex:1;">
						<label for="rel_min_php"><strong><?php esc_html_e( 'Min PHP', 'wpcalibrate-license-manager' ); ?></strong></label>
						<input type="text" id="rel_min_php" name="min_php_version" class="widefat" value="8.2" />
					</p>
				</div>

				<p>
					<label for="rel_changelog"><strong><?php esc_html_e( 'Changelog / Release Notes', 'wpcalibrate-license-manager' ); ?></strong></label>
					<textarea id="rel_changelog" name="changelog" class="widefat" rows="3" placeholder="* Feature: Added new capability&#10;* Fix: Resolved edge case"></textarea>
				</p>

				<p>
					<label for="rel_upgrade_notice"><strong><?php esc_html_e( 'Upgrade Notice (Shown on WP update screen)', 'wpcalibrate-license-manager' ); ?></strong></label>
					<input type="text" id="rel_upgrade_notice" name="upgrade_notice" class="widefat" placeholder="Important security & feature enhancements." />
				</p>

				<p>
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Publish Release', 'wpcalibrate-license-manager' ); ?>" />
				</p>
			</form>
		</div>
	</div>
</div>
