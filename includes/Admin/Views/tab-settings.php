<?php
/**
 * Admin Tab: Settings.
 *
 * @package WPCalibrate\LicenseManager\Admin\Views
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = get_option( 'wpcalibrate_lm_settings', array() );
?>
<div class="wpcalibrate-card wpcalibrate-settings-card">
	<h2><?php esc_html_e( 'Licensing Platform Configuration', 'wpcalibrate-license-manager' ); ?></h2>

	<form method="post">
		<?php wp_nonce_field( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_lm_action" value="save_settings" />

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="set_val_cache"><?php esc_html_e( 'Client Validation Cache Interval', 'wpcalibrate-license-manager' ); ?></label></th>
				<td>
					<input type="number" id="set_val_cache" name="validation_cache_interval_hours" value="<?php echo esc_attr( (string) ( $settings['validation_cache_interval_hours'] ?? 24 ) ); ?>" class="small-text" min="1" max="168" /> <?php esc_html_e( 'hours', 'wpcalibrate-license-manager' ); ?>
					<p class="description"><?php esc_html_e( 'Standard interval for client SDK to perform background validation calls (Default: 24h).', 'wpcalibrate-license-manager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="set_grace_period"><?php esc_html_e( 'Server Outage Grace Period', 'wpcalibrate-license-manager' ); ?></label></th>
				<td>
					<input type="number" id="set_grace_period" name="grace_period_days" value="<?php echo esc_attr( (string) ( $settings['grace_period_days'] ?? 7 ) ); ?>" class="small-text" min="1" max="30" /> <?php esc_html_e( 'days', 'wpcalibrate-license-manager' ); ?>
					<p class="description"><?php esc_html_e( 'Allow existing valid installations to maintain active status if licensing server is temporarily unreachable (Default: 7 days).', 'wpcalibrate-license-manager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="set_staging_patterns"><?php esc_html_e( 'Recognized Staging Domain Patterns', 'wpcalibrate-license-manager' ); ?></label></th>
				<td>
					<textarea id="set_staging_patterns" name="staging_patterns" rows="5" class="large-text code"><?php echo esc_textarea( (string) ( $settings['staging_patterns'] ?? "localhost\n127.0.0.1\n*.local\n*.test\n*.staging.*\n*-staging.*\n*.dev" ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One pattern per line. Sites matching these domain patterns do not consume client license seats.', 'wpcalibrate-license-manager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="set_token_ttl"><?php esc_html_e( 'Download Token Lifetime', 'wpcalibrate-license-manager' ); ?></label></th>
				<td>
					<input type="number" id="set_token_ttl" name="download_token_ttl_seconds" value="<?php echo esc_attr( (string) ( $settings['download_token_ttl_seconds'] ?? 900 ) ); ?>" class="small-text" min="60" max="86400" /> <?php esc_html_e( 'seconds', 'wpcalibrate-license-manager' ); ?>
					<p class="description"><?php esc_html_e( 'Short-lived validity window for HMAC-signed update package URLs (Default: 900s / 15 minutes).', 'wpcalibrate-license-manager' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="set_rate_limit"><?php esc_html_e( 'REST API Rate Limit', 'wpcalibrate-license-manager' ); ?></label></th>
				<td>
					<input type="number" id="set_rate_limit" name="rate_limit_requests" value="<?php echo esc_attr( (string) ( $settings['rate_limit_requests'] ?? 60 ) ); ?>" class="small-text" min="10" /> <?php esc_html_e( 'requests per', 'wpcalibrate-license-manager' ); ?>
					<input type="number" name="rate_limit_window_seconds" value="<?php echo esc_attr( (string) ( $settings['rate_limit_window_seconds'] ?? 60 ) ); ?>" class="small-text" min="10" /> <?php esc_html_e( 'seconds per IP', 'wpcalibrate-license-manager' ); ?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'WooCommerce Integration', 'wpcalibrate-license-manager' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="checkbox" name="wc_auto_license" value="1" <?php checked( ! empty( $settings['wc_auto_license'] ) ); ?> />
							<?php esc_html_e( 'Automatically generate license on paid/completed orders for mapped products', 'wpcalibrate-license-manager' ); ?>
						</label>
						<br><br>
						<label for="set_refund_action"><strong><?php esc_html_e( 'Refund / Cancellation Policy:', 'wpcalibrate-license-manager' ); ?></strong></label>
						<select id="set_refund_action" name="wc_refund_action">
							<option value="suspend" <?php selected( ( $settings['wc_refund_action'] ?? 'suspend' ), 'suspend' ); ?>><?php esc_html_e( 'Suspend License (Reversible)', 'wpcalibrate-license-manager' ); ?></option>
							<option value="revoke" <?php selected( ( $settings['wc_refund_action'] ?? '' ), 'revoke' ); ?>><?php esc_html_e( 'Revoke License Permanently', 'wpcalibrate-license-manager' ); ?></option>
							<option value="none" <?php selected( ( $settings['wc_refund_action'] ?? '' ), 'none' ); ?>><?php esc_html_e( 'No Action (Retain License)', 'wpcalibrate-license-manager' ); ?></option>
						</select>
					</fieldset>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Data Retention on Uninstall', 'wpcalibrate-license-manager' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="data_retention_policy" value="preserve" <?php checked( ( $settings['data_retention_policy'] ?? 'preserve' ), 'preserve' ); ?> />
							<strong><?php esc_html_e( 'Preserve Data (Recommended)', 'wpcalibrate-license-manager' ); ?></strong> &mdash; <?php esc_html_e( 'Keep licenses, activations, and product history intact if plugin is deleted.', 'wpcalibrate-license-manager' ); ?>
						</label>
						<br><br>
						<label>
							<input type="radio" name="data_retention_policy" value="delete_all" <?php checked( ( $settings['data_retention_policy'] ?? '' ), 'delete_all' ); ?> />
							<span style="color:#b32d2e;"><strong><?php esc_html_e( 'Delete All Data on Uninstall', 'wpcalibrate-license-manager' ); ?></strong></span> &mdash; <?php esc_html_e( 'Permanently wipe all tables, options, and license keys upon plugin deletion.', 'wpcalibrate-license-manager' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="set_log_retention"><?php esc_html_e( 'Audit Log Retention', 'wpcalibrate-license-manager' ); ?></label></th>
				<td>
					<input type="number" id="set_log_retention" name="log_retention_days" value="<?php echo esc_attr( (string) ( $settings['log_retention_days'] ?? 90 ) ); ?>" class="small-text" min="7" /> <?php esc_html_e( 'days', 'wpcalibrate-license-manager' ); ?>
					<p class="description"><?php esc_html_e( 'Prune diagnostic audit events older than this threshold during daily maintenance cron.', 'wpcalibrate-license-manager' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Configuration', 'wpcalibrate-license-manager' ); ?>" />
		</p>
	</form>
</div>
