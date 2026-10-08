<?php
/**
 * Admin Tab: Overview & Guide.
 *
 * Detailed explanation of the plugin's purpose, how to use it, and architectural method.
 *
 * @package WPCalibrate\LicenseManager\Admin\Views
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpcalibrate-overview-container">

	<!-- Hero Banner -->
	<div class="wpcalibrate-card wpcalibrate-hero-card">
		<div class="wpcalibrate-hero-content">
			<span class="wpcalibrate-pill-badge"><?php esc_html_e( 'Architectural Guide & Documentation', 'wpcalibrate-license-manager' ); ?></span>
			<h2><?php esc_html_e( 'WPCalibrate License Manager & Reusable SDK', 'wpcalibrate-license-manager' ); ?></h2>
			<p class="wpcalibrate-lead">
				<?php esc_html_e( 'The central licensing authority, product versioning repository, and private update distribution platform for commercial WPCalibrate WordPress plugins and extensions.', 'wpcalibrate-license-manager' ); ?>
			</p>
		</div>
	</div>

	<!-- 3 Key Pillars -->
	<div class="wpcalibrate-dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
		<div class="wpcalibrate-card">
			<h3 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-shield-alt" style="color:#2271b1;"></span>
				<?php esc_html_e( '1. Authoritative Licensing', 'wpcalibrate-license-manager' ); ?>
			</h3>
			<p>
				<?php esc_html_e( 'Issues cryptographically secure 128-bit CSPRNG keys (WPC-XXXX-XXXX-XXXX-XXXX) with SHA-256 hashed lookups and AES-256-CBC encrypted storage. Enforces configurable seat limits with domain binding and automatic staging/localhost exemptions.', 'wpcalibrate-license-manager' ); ?>
			</p>
		</div>

		<div class="wpcalibrate-card">
			<h3 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-update" style="color:#00a32a;"></span>
				<?php esc_html_e( '2. Private WordPress Updates', 'wpcalibrate-license-manager' ); ?>
			</h3>
			<p>
				<?php esc_html_e( 'Delivers private premium plugin updates directly through the native WordPress Plugins screen. Update packages are authorized via short-lived HMAC-SHA256 signed download tokens; permanent license keys are never exposed in package URLs.', 'wpcalibrate-license-manager' ); ?>
			</p>
		</div>

		<div class="wpcalibrate-card">
			<h3 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-cart" style="color:#8250df;"></span>
				<?php esc_html_e( '3. WooCommerce Automation', 'wpcalibrate-license-manager' ); ?>
			</h3>
			<p>
				<?php esc_html_e( 'HPOS-compatible commerce adapter automatically provisions unique licenses when mapped WooCommerce products are purchased. Idempotent order processing ensures repeated hook firings never generate duplicate keys.', 'wpcalibrate-license-manager' ); ?>
			</p>
		</div>
	</div>

	<!-- Step-by-Step How to Use Guide -->
	<div class="wpcalibrate-card">
		<h2><?php esc_html_e( 'How to Use: Step-by-Step Workflow Guide', 'wpcalibrate-license-manager' ); ?></h2>

		<div class="wpcalibrate-workflow-steps">
			<div class="wpcalibrate-step-item">
				<div class="wpcalibrate-step-number">1</div>
				<div class="wpcalibrate-step-text">
					<h4><?php esc_html_e( 'Step 1: Register Your Product', 'wpcalibrate-license-manager' ); ?></h4>
					<p><?php esc_html_e( 'Navigate to the Products tab and click "Add Product". Set your plugin name, exact folder slug (e.g. wpcalibrate-pro), current stable version, minimum requirements (PHP 8.2+, WP 6.5+), and optional WooCommerce product ID mapping.', 'wpcalibrate-license-manager' ); ?></p>
				</div>
			</div>

			<div class="wpcalibrate-step-item">
				<div class="wpcalibrate-step-number">2</div>
				<div class="wpcalibrate-step-text">
					<h4><?php esc_html_e( 'Step 2: Upload or Register Releases', 'wpcalibrate-license-manager' ); ?></h4>
					<p><?php esc_html_e( 'In the Releases tab, upload the plugin ZIP archive for each release version. Packages are stored in protected storage with SHA-256 integrity hashes and served exclusively to authorized client installations.', 'wpcalibrate-license-manager' ); ?></p>
				</div>
			</div>

			<div class="wpcalibrate-step-item">
				<div class="wpcalibrate-step-number">3</div>
				<div class="wpcalibrate-step-text">
					<h4><?php esc_html_e( 'Step 3: Issue or Sell Licenses', 'wpcalibrate-license-manager' ); ?></h4>
					<p><?php esc_html_e( 'Licenses can be generated manually in the Licenses tab (with customizable seat limits and lifetime/expiring status) or automatically generated when customers complete purchases on WooCommerce.', 'wpcalibrate-license-manager' ); ?></p>
				</div>
			</div>

			<div class="wpcalibrate-step-item">
				<div class="wpcalibrate-step-number">4</div>
				<div class="wpcalibrate-step-text">
					<h4><?php esc_html_e( 'Step 4: Embed the Licensing SDK in Your Commercial Plugin', 'wpcalibrate-license-manager' ); ?></h4>
					<p><?php esc_html_e( 'Copy the licensing/wpcalibrate-licensing-sdk/ folder into your commercial plugin and bootstrap it with a simple configuration array. The SDK handles background validations, outage grace periods, and native WordPress updates with zero global collisions.', 'wpcalibrate-license-manager' ); ?></p>
				</div>
			</div>

			<div class="wpcalibrate-step-item">
				<div class="wpcalibrate-step-number">5</div>
				<div class="wpcalibrate-step-text">
					<h4><?php esc_html_e( 'Step 5: Client Activates in WordPress Dashboard', 'wpcalibrate-license-manager' ); ?></h4>
					<p><?php esc_html_e( 'The customer enters their license key in your plugin settings. Once activated, the client installation validates its seat, displays expiry info, and receives 1-click update notices whenever you publish a new release.', 'wpcalibrate-license-manager' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Minimal Integration Code Snippet -->
	<div class="wpcalibrate-card">
		<h2><?php esc_html_e( 'Method: Reusable SDK Integration Code', 'wpcalibrate-license-manager' ); ?></h2>
		<p><?php esc_html_e( 'Add this minimal initialization snippet to any commercial WPCalibrate plugin to enable full licensing and auto-updates:', 'wpcalibrate-license-manager' ); ?></p>

		<pre style="background:#282c34;color:#abb2bf;padding:16px;border-radius:4px;overflow-x:auto;font-size:13px;line-height:1.5;"><code><span style="color:#e06c75;">&lt;?php</span>
<span style="color:#5c6370;">// 1. Require SDK bootstrap from your plugin</span>
<span style="color:#c678dd;">require_once</span> <span style="color:#e5c07b;">__DIR__</span> . <span style="color:#98c379;">'/licensing/wpcalibrate-licensing-sdk/bootstrap.php'</span>;

<span style="color:#c678dd;">use</span> <span style="color:#e5c07b;">WPCalibrate\LicensingSDK\Client</span>;
<span style="color:#c678dd;">use</span> <span style="color:#e5c07b;">WPCalibrate\LicensingSDK\Config</span>;

<span style="color:#5c6370;">// 2. Initialize with your product configuration</span>
<span style="color:#e06c75;">$config</span> = <span style="color:#c678dd;">new</span> <span style="color:#e5c07b;">Config</span>([
    <span style="color:#98c379;">'product_id'</span>          =&gt; <span style="color:#d19a66;">1</span>, <span style="color:#5c6370;">// Numeric Product ID on licensing server</span>
    <span style="color:#98c379;">'plugin_slug'</span>         =&gt; <span style="color:#98c379;">'wpcalibrate-pro'</span>,
    <span style="color:#98c379;">'plugin_name'</span>         =&gt; <span style="color:#98c379;">'WPCalibrate Pro'</span>,
    <span style="color:#98c379;">'current_version'</span>     =&gt; <span style="color:#98c379;">'1.0.0'</span>,
    <span style="color:#98c379;">'main_plugin_file'</span>    =&gt; <span style="color:#e5c07b;">__FILE__</span>,
    <span style="color:#98c379;">'license_server_url'</span>  =&gt; <span style="color:#98c379;">'https://marketplace.wpcalibrate.com'</span>,
    <span style="color:#98c379;">'settings_page_slug'</span>  =&gt; <span style="color:#98c379;">'wpcalibrate-pro'</span>,
    <span style="color:#98c379;">'required_capability'</span> =&gt; <span style="color:#98c379;">'manage_options'</span>,
    <span style="color:#98c379;">'text_domain'</span>         =&gt; <span style="color:#98c379;">'wpcalibrate-pro'</span>,
    <span style="color:#98c379;">'renewal_url'</span>         =&gt; <span style="color:#98c379;">'https://marketplace.wpcalibrate.com/checkout?renew=1'</span>,
]);

<span style="color:#e06c75;">$licensing</span> = <span style="color:#e5c07b;">Client</span>::<span style="color:#61afef;">init</span>( <span style="color:#e06c75;">$config</span> );

<span style="color:#5c6370;">// 3. Render license section inside your settings page tab</span>
<span style="color:#61afef;">add_action</span>( <span style="color:#98c379;">'wpcalibrate_pro_render_settings_tab'</span>, <span style="color:#c678dd;">function</span>() <span style="color:#c678dd;">use</span> ( <span style="color:#e06c75;">$licensing</span> ) {
    <span style="color:#e06c75;">$licensing</span>-&gt;<span style="color:#61afef;">render_license_section</span>();
});

<span style="color:#5c6370;">// 4. (Optional) Check license status for feature gating</span>
<span style="color:#c678dd;">if</span> ( <span style="color:#e06c75;">$licensing</span>-&gt;<span style="color:#61afef;">is_license_active</span>() ) {
    <span style="color:#5c6370;">// Run premium feature logic</span>
}</code></pre>
	</div>

	<!-- REST API Endpoints Overview -->
	<div class="wpcalibrate-card">
		<h2><?php esc_html_e( 'REST API Endpoints Specification', 'wpcalibrate-license-manager' ); ?></h2>
		<p><?php esc_html_e( 'All communication between client plugins and this licensing server uses the following versioned REST endpoints:', 'wpcalibrate-license-manager' ); ?></p>

		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th style="width:200px;"><?php esc_html_e( 'Endpoint', 'wpcalibrate-license-manager' ); ?></th>
					<th style="width:80px;"><?php esc_html_e( 'Method', 'wpcalibrate-license-manager' ); ?></th>
					<th><?php esc_html_e( 'Description & Payload', 'wpcalibrate-license-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>/wpcalibrate-license/v1/activate</code></td>
					<td><span class="wpcalibrate-badge-production">POST</span></td>
					<td><?php esc_html_e( 'Activates a license key for a site. Binds normalized site identity, enforces seat limits, and handles staging exemptions idempotently.', 'wpcalibrate-license-manager' ); ?></td>
				</tr>
				<tr>
					<td><code>/wpcalibrate-license/v1/deactivate</code></td>
					<td><span class="wpcalibrate-badge-production">POST</span></td>
					<td><?php esc_html_e( 'Deactivates a license key for a site, releasing its seat count back to the license.', 'wpcalibrate-license-manager' ); ?></td>
				</tr>
				<tr>
					<td><code>/wpcalibrate-license/v1/validate</code></td>
					<td><span class="wpcalibrate-badge-production">POST</span></td>
					<td><?php esc_html_e( 'Validates current license status (active, expired, suspended, revoked). Executed in background with 24h transient caching.', 'wpcalibrate-license-manager' ); ?></td>
				</tr>
				<tr>
					<td><code>/wpcalibrate-license/v1/update-check</code></td>
					<td><span class="wpcalibrate-badge-production">POST</span></td>
					<td><?php esc_html_e( 'Queries for newer release versions. If an active license exists, provides a short-lived download token URL.', 'wpcalibrate-license-manager' ); ?></td>
				</tr>
				<tr>
					<td><code>/wpcalibrate-license/v1/download-token</code></td>
					<td><span class="wpcalibrate-badge-production">POST</span></td>
					<td><?php esc_html_e( 'Issues a signed HMAC-SHA256 token authorizing package streaming for the requested product release.', 'wpcalibrate-license-manager' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

</div>
