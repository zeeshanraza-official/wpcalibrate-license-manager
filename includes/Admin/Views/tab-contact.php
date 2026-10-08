<?php
/**
 * Admin Tab: Contact & Support.
 *
 * @package WPCalibrate\LicenseManager\Admin\Views
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dark_icon_path = WPCALIBRATE_LM_DIR . 'branding/icon-dark.png';
$dark_icon_url  = file_exists( $dark_icon_path ) ? WPCALIBRATE_LM_URL . 'branding/icon-dark.png' : '';
?>
<div class="wpcalibrate-card wpcalibrate-contact-card">
	<div class="wpcalibrate-contact-header">
		<?php if ( ! empty( $dark_icon_url ) ) : ?>
			<img src="<?php echo esc_url( $dark_icon_url ); ?>" alt="WPCalibrate" style="width:48px;height:auto;margin-right:15px;vertical-align:middle;" />
		<?php endif; ?>
		<div style="display:inline-block;vertical-align:middle;">
			<h2 style="margin:0 0 5px 0;"><?php esc_html_e( 'WPCalibrate Official Support & Marketplace', 'wpcalibrate-license-manager' ); ?></h2>
			<p style="margin:0;color:#646970;"><?php esc_html_e( 'Enterprise WordPress plugin architecture and licensing solutions.', 'wpcalibrate-license-manager' ); ?></p>
		</div>
	</div>

	<hr style="margin:20px 0;border:0;border-top:1px solid #dcdcde;">

	<div class="wpcalibrate-contact-grid">
		<div class="wpcalibrate-contact-channel">
			<h3><?php esc_html_e( 'Customer & Technical Support', 'wpcalibrate-license-manager' ); ?></h3>
			<p>
				<strong><?php esc_html_e( 'Email:', 'wpcalibrate-license-manager' ); ?></strong>
				<a href="mailto:support@wpcalibrate.com">support@wpcalibrate.com</a>
			</p>
			<p>
				<strong><?php esc_html_e( 'Phone / WhatsApp:', 'wpcalibrate-license-manager' ); ?></strong>
				<a href="https://wa.me/447474795976" target="_blank" rel="noopener noreferrer">+447474795976</a>
			</p>
		</div>

		<div class="wpcalibrate-contact-channel">
			<h3><?php esc_html_e( 'Web Resources', 'wpcalibrate-license-manager' ); ?></h3>
			<p>
				<strong><?php esc_html_e( 'Official Website:', 'wpcalibrate-license-manager' ); ?></strong>
				<a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer">https://wpcalibrate.com</a>
			</p>
			<p>
				<strong><?php esc_html_e( 'Marketplace & Extensions:', 'wpcalibrate-license-manager' ); ?></strong>
				<a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer">https://marketplace.wpcalibrate.com/</a>
			</p>
		</div>
	</div>

	<div class="wpcalibrate-contact-footer-note" style="margin-top:20px;padding:12px;background:#f0f6fc;border-left:4px solid #72aee6;">
		<p style="margin:0;">
			<?php esc_html_e( 'For SDK documentation, API integration details, or enterprise multi-tier licensing setups, visit our documentation portal on marketplace.wpcalibrate.com.', 'wpcalibrate-license-manager' ); ?>
		</p>
	</div>
</div>
