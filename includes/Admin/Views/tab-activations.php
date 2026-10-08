<?php
/**
 * Admin Tab: Activations.
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

$activations       = $this->activation_repo->get_all( array( 'search' => $search, 'limit' => $limit, 'offset' => $offset ) );
$total_activations = $this->activation_repo->count_all( array( 'search' => $search ) );
?>
<div class="wpcalibrate-card">
	<div class="tablenav top">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="alignleft">
			<input type="hidden" name="page" value="wpcalibrate-license-manager" />
			<input type="hidden" name="tab" value="activations" />
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search domain, URL, IP...', 'wpcalibrate-license-manager' ); ?>" />
			<input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'wpcalibrate-license-manager' ); ?>" />
		</form>
		<div class="tablenav-pages alignright">
			<span class="displaying-num"><?php printf( esc_html__( '%d activations', 'wpcalibrate-license-manager' ), (int) $total_activations ); ?></span>
		</div>
	</div>

	<table class="widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'License ID', 'wpcalibrate-license-manager' ); ?></th>
				<th><?php esc_html_e( 'Site Domain', 'wpcalibrate-license-manager' ); ?></th>
				<th><?php esc_html_e( 'Site URL', 'wpcalibrate-license-manager' ); ?></th>
				<th><?php esc_html_e( 'Environment', 'wpcalibrate-license-manager' ); ?></th>
				<th><?php esc_html_e( 'WP / Plugin Version', 'wpcalibrate-license-manager' ); ?></th>
				<th><?php esc_html_e( 'IP Address', 'wpcalibrate-license-manager' ); ?></th>
				<th><?php esc_html_e( 'Last Validated', 'wpcalibrate-license-manager' ); ?></th>
				<th><?php esc_html_e( 'Action', 'wpcalibrate-license-manager' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $activations ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No active site installations found.', 'wpcalibrate-license-manager' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $activations as $act ) : ?>
					<tr>
						<td>#<?php echo esc_html( (string) $act->license_id ); ?></td>
						<td><strong><?php echo esc_html( $act->site_domain ); ?></strong></td>
						<td><a href="<?php echo esc_url( $act->site_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $act->site_url ); ?></a></td>
						<td>
							<?php if ( $act->is_staging ) : ?>
								<span class="wpcalibrate-badge-staging"><?php esc_html_e( 'Staging (Free)', 'wpcalibrate-license-manager' ); ?></span>
							<?php else : ?>
								<span class="wpcalibrate-badge-production"><?php esc_html_e( 'Production', 'wpcalibrate-license-manager' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( ( $act->wp_version ?: '-' ) . ' / ' . ( $act->plugin_version ?: '-' ) ); ?></td>
						<td><code><?php echo esc_html( $act->site_ip ?: '-' ); ?></code></td>
						<td><?php echo esc_html( $act->last_validated_at ); ?></td>
						<td>
							<form method="post" onsubmit="return confirm('<?php esc_attr_e( 'Deactivate this site and release its license seat?', 'wpcalibrate-license-manager' ); ?>');">
								<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
								<input type="hidden" name="wpcalibrate_lm_action" value="deactivate_site" />
								<input type="hidden" name="activation_id" value="<?php echo esc_attr( (string) $act->id ); ?>" />
								<input type="submit" class="button button-small button-link-delete" value="<?php esc_attr_e( 'Deactivate', 'wpcalibrate-license-manager' ); ?>" />
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
