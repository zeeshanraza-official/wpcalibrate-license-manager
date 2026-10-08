<?php
/**
 * Admin Tab: Diagnostics.
 *
 * @package WPCalibrate\LicenseManager\Admin\Views
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$recent_events = $this->event_repo->get_recent( array( 'limit' => 25 ) );
$secret_set    = ! empty( get_option( 'wpcalibrate_lm_secret_key' ) );
$cron_next     = wp_next_scheduled( 'wpcalibrate_lm_daily_maintenance' );
?>
<div class="wpcalibrate-two-col">
	<div class="wpcalibrate-col-list">
		<div class="wpcalibrate-card">
			<h2><?php esc_html_e( 'System Environment & Diagnostics', 'wpcalibrate-license-manager' ); ?></h2>
			<table class="widefat fixed striped">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Version', 'wpcalibrate-license-manager' ); ?></strong></td>
						<td><code><?php echo esc_html( PHP_VERSION ); ?></code> (Target: 8.2 &amp; 8.3+)</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WordPress Version', 'wpcalibrate-license-manager' ); ?></strong></td>
						<td><code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code> (Tested: 7.1.3)</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WooCommerce Status', 'wpcalibrate-license-manager' ); ?></strong></td>
						<td>
							<?php
							$wc_active = class_exists( 'WooCommerce' );
							$wc_ver    = defined( 'WC_VERSION' ) ? WC_VERSION : ( ( function_exists( 'WC' ) && isset( WC()->version ) ) ? WC()->version : '' );
							if ( $wc_active ) :
							?>
								<span class="wpcalibrate-status-badge status-active"><?php esc_html_e( 'Active', 'wpcalibrate-license-manager' ); ?><?php echo $wc_ver ? ' (v' . esc_html( $wc_ver ) . ')' : ''; ?></span>
							<?php else : ?>
								<span class="description"><?php esc_html_e( 'Not installed / inactive (Core licensing operating standalone)', 'wpcalibrate-license-manager' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'OpenSSL Extension (AES-256)', 'wpcalibrate-license-manager' ); ?></strong></td>
						<td><code><?php echo extension_loaded( 'openssl' ) ? esc_html__( 'Enabled', 'wpcalibrate-license-manager' ) : esc_html__( 'Missing', 'wpcalibrate-license-manager' ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Cryptographic Secret Key', 'wpcalibrate-license-manager' ); ?></strong></td>
						<td><?php echo $secret_set ? '<span class="wpcalibrate-status-badge status-active">' . esc_html__( 'Configured', 'wpcalibrate-license-manager' ) . '</span>' : '<span class="wpcalibrate-status-badge status-revoked">' . esc_html__( 'Missing', 'wpcalibrate-license-manager' ) . '</span>'; ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Scheduled Daily Maintenance Cron', 'wpcalibrate-license-manager' ); ?></strong></td>
						<td><?php echo $cron_next ? esc_html( sprintf( __( 'Next run in %d minutes', 'wpcalibrate-license-manager' ), (int) round( ( $cron_next - time() ) / 60 ) ) ) : esc_html__( 'Not scheduled', 'wpcalibrate-license-manager' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Database Schema Version', 'wpcalibrate-license-manager' ); ?></strong></td>
						<td><code>v<?php echo esc_html( (string) get_option( 'wpcalibrate_lm_db_version', '1.0.0' ) ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'REST API Endpoint', 'wpcalibrate-license-manager' ); ?></strong></td>
						<td><code><?php echo esc_html( rest_url( 'wpcalibrate-license/v1/' ) ); ?></code></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<div class="wpcalibrate-col-form">
		<div class="wpcalibrate-card">
			<h2><?php esc_html_e( 'Security Audit Log (Recent 25)', 'wpcalibrate-license-manager' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Full license keys and credentials are automatically stripped and masked.', 'wpcalibrate-license-manager' ); ?></p>
			<?php if ( empty( $recent_events ) ) : ?>
				<p><?php esc_html_e( 'No events recorded.', 'wpcalibrate-license-manager' ); ?></p>
			<?php else : ?>
				<div style="max-height: 400px; overflow-y: auto;">
					<table class="widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Event', 'wpcalibrate-license-manager' ); ?></th>
								<th><?php esc_html_e( 'Details', 'wpcalibrate-license-manager' ); ?></th>
								<th><?php esc_html_e( 'Time', 'wpcalibrate-license-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_events as $ev ) : ?>
								<tr>
									<td><code><?php echo esc_html( $ev->event_type ); ?></code></td>
									<td>
										<small>
											<?php if ( $ev->site_domain ) : ?>Domain: <?php echo esc_html( $ev->site_domain ); ?><br><?php endif; ?>
											<?php if ( $ev->context_data ) : ?>Context: <?php echo esc_html( $ev->context_data ); ?><?php endif; ?>
										</small>
									</td>
									<td><small><?php echo esc_html( $ev->created_at ); ?></small></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
