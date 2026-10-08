<?php
/**
 * Admin View Header.
 *
 * @package WPCalibrate\LicenseManager\Admin\Views
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_tab = sanitize_key( $_GET['tab'] ?? 'overview' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$tabs = array(
	'overview'    => __( 'Overview', 'wpcalibrate-license-manager' ),
	'dashboard'   => __( 'Dashboard', 'wpcalibrate-license-manager' ),
	'products'    => __( 'Products', 'wpcalibrate-license-manager' ),
	'licenses'    => __( 'Licenses', 'wpcalibrate-license-manager' ),
	'activations' => __( 'Activations', 'wpcalibrate-license-manager' ),
	'releases'    => __( 'Releases', 'wpcalibrate-license-manager' ),
	'settings'    => __( 'Settings', 'wpcalibrate-license-manager' ),
	'diagnostics' => __( 'Diagnostics', 'wpcalibrate-license-manager' ),
	'contact'     => __( 'Contact & Support', 'wpcalibrate-license-manager' ),
);

$dark_icon_path = WPCALIBRATE_LM_DIR . 'branding/icon-dark.png';
$dark_icon_url  = file_exists( $dark_icon_path ) ? WPCALIBRATE_LM_URL . 'branding/icon-dark.png' : '';
?>
<div class="wrap wpcalibrate-lm-wrap">
	<div class="wpcalibrate-header-bar">
		<div class="wpcalibrate-branding">
			<?php if ( ! empty( $dark_icon_url ) ) : ?>
				<img src="<?php echo esc_url( $dark_icon_url ); ?>" alt="WPCalibrate" class="wpcalibrate-logo-icon" />
			<?php endif; ?>
			<div class="wpcalibrate-title-group">
				<h1 class="wpcalibrate-main-title"><?php esc_html_e( 'WPCalibrate License Manager', 'wpcalibrate-license-manager' ); ?></h1>
				<span class="wpcalibrate-version-badge">v<?php echo esc_html( WPCALIBRATE_LM_VERSION ); ?></span>
			</div>
		</div>
		<div class="wpcalibrate-header-meta">
			<a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer" class="button button-secondary">
				<?php esc_html_e( 'Marketplace Portal', 'wpcalibrate-license-manager' ); ?>
			</a>
		</div>
	</div>

	<?php settings_errors( 'wpcalibrate_lm' ); ?>

	<nav class="nav-tab-wrapper wpcalibrate-nav-tabs">
		<?php foreach ( $tabs as $tab_key => $tab_title ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wpcalibrate-license-manager', 'tab' => $tab_key ), admin_url( 'admin.php' ) ) ); ?>"
				class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_title ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="wpcalibrate-tab-content">
