<?php
/**
 * Admin Tab: Dashboard.
 *
 * @package WPCalibrate\LicenseManager\Admin\Views
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total_products    = $this->product_repo->count_all();
$total_licenses    = $this->license_repo->count_all();
$active_licenses   = $this->license_repo->count_all( array( 'status' => 'active' ) );
$total_activations = $this->activation_repo->count_all();
$recent_events     = $this->event_repo->get_recent( array( 'limit' => 10 ) );
?>
<div class="wpcalibrate-dashboard-grid">
	<div class="wpcalibrate-stat-card">
		<span class="stat-number"><?php echo esc_html( (string) $total_products ); ?></span>
		<span class="stat-label"><?php esc_html_e( 'Total Products', 'wpcalibrate-license-manager' ); ?></span>
	</div>
	<div class="wpcalibrate-stat-card">
		<span class="stat-number"><?php echo esc_html( (string) $active_licenses ); ?></span>
		<span class="stat-label"><?php esc_html_e( 'Active Licenses', 'wpcalibrate-license-manager' ); ?></span>
	</div>
	<div class="wpcalibrate-stat-card">
		<span class="stat-number"><?php echo esc_html( (string) $total_licenses ); ?></span>
		<span class="stat-label"><?php esc_html_e( 'Total Licenses Issued', 'wpcalibrate-license-manager' ); ?></span>
	</div>
	<div class="wpcalibrate-stat-card">
		<span class="stat-number"><?php echo esc_html( (string) $total_activations ); ?></span>
		<span class="stat-label"><?php esc_html_e( 'Active Site Activations', 'wpcalibrate-license-manager' ); ?></span>
	</div>
</div>

<div class="wpcalibrate-dashboard-sections">
	<div class="wpcalibrate-card">
		<h2><?php esc_html_e( 'Quick Start & Architecture Overview', 'wpcalibrate-license-manager' ); ?></h2>
		<p><?php esc_html_e( 'WPCalibrate License Manager acts as the central authority for commercial WordPress plugins.', 'wpcalibrate-license-manager' ); ?></p>
		<ul class="wpcalibrate-bullet-list">
			<li><strong><?php esc_html_e( 'Products:', 'wpcalibrate-license-manager' ); ?></strong> <?php esc_html_e( 'Define plugin identifiers, minimum PHP/WP versions, and WooCommerce product associations.', 'wpcalibrate-license-manager' ); ?></li>
			<li><strong><?php esc_html_e( 'Releases:', 'wpcalibrate-license-manager' ); ?></strong> <?php esc_html_e( 'Upload protected plugin ZIP packages and write release notes for automatic client update checks.', 'wpcalibrate-license-manager' ); ?></li>
			<li><strong><?php esc_html_e( 'Licensing SDK:', 'wpcalibrate-license-manager' ); ?></strong> <?php esc_html_e( 'Embed the lightweight SDK in client plugins to handle automated activation and in-dashboard updates.', 'wpcalibrate-license-manager' ); ?></li>
		</ul>
	</div>

	<div class="wpcalibrate-card">
		<h2><?php esc_html_e( 'Recent Security & Licensing Events', 'wpcalibrate-license-manager' ); ?></h2>
		<?php if ( empty( $recent_events ) ) : ?>
			<p class="description"><?php esc_html_e( 'No recent events recorded yet.', 'wpcalibrate-license-manager' ); ?></p>
		<?php else : ?>
			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Event', 'wpcalibrate-license-manager' ); ?></th>
						<th><?php esc_html_e( 'Domain', 'wpcalibrate-license-manager' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'wpcalibrate-license-manager' ); ?></th>
						<th><?php esc_html_e( 'Timestamp', 'wpcalibrate-license-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent_events as $event ) : ?>
						<tr>
							<td><code><?php echo esc_html( $event->event_type ); ?></code></td>
							<td><?php echo esc_html( $event->site_domain ?: '-' ); ?></td>
							<td><?php echo esc_html( $event->ip_address ?: '-' ); ?></td>
							<td><?php echo esc_html( $event->created_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
