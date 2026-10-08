<?php
/**
 * SDK Configuration Object.
 *
 * @package WPCalibrate\LicensingSDK
 */

declare(strict_types=1);

namespace WPCalibrate\LicensingSDK;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Config
 */
final class Config {

	private int $product_id;
	private string $plugin_slug;
	private string $plugin_name;
	private string $current_version;
	private string $main_plugin_file;
	private string $license_server_url;
	private string $settings_page_slug;
	private string $required_capability;
	private string $text_domain;
	private string $renewal_url;

	public function __construct( array $data ) {
		$this->product_id          = (int) ( $data['product_id'] ?? 0 );
		$this->plugin_slug         = sanitize_title( (string) ( $data['plugin_slug'] ?? '' ) );
		$this->plugin_name         = sanitize_text_field( (string) ( $data['plugin_name'] ?? '' ) );
		$this->current_version      = sanitize_text_field( (string) ( $data['current_version'] ?? '1.0.0' ) );
		$this->main_plugin_file    = (string) ( $data['main_plugin_file'] ?? '' );
		$this->license_server_url  = esc_url_raw( rtrim( (string) ( $data['license_server_url'] ?? 'https://marketplace.wpcalibrate.com' ), '/' ) );
		$this->settings_page_slug  = sanitize_key( (string) ( $data['settings_page_slug'] ?? $this->plugin_slug ) );
		$this->required_capability = sanitize_key( (string) ( $data['required_capability'] ?? 'manage_options' ) );
		$this->text_domain         = sanitize_key( (string) ( $data['text_domain'] ?? $this->plugin_slug ) );
		$this->renewal_url         = esc_url_raw( (string) ( $data['renewal_url'] ?? 'https://marketplace.wpcalibrate.com/' ) );
	}

	public function get_product_id(): int { return $this->product_id; }
	public function get_plugin_slug(): string { return $this->plugin_slug; }
	public function get_plugin_name(): string { return $this->plugin_name; }
	public function get_current_version(): string { return $this->current_version; }
	public function get_main_plugin_file(): string { return $this->main_plugin_file; }
	public function get_license_server_url(): string { return $this->license_server_url; }
	public function get_settings_page_slug(): string { return $this->settings_page_slug; }
	public function get_required_capability(): string { return $this->required_capability; }
	public function get_text_domain(): string { return $this->text_domain; }
	public function get_renewal_url(): string { return $this->renewal_url; }

	/**
	 * Get option key for storing license state.
	 *
	 * @return string
	 */
	public function get_storage_key(): string {
		return 'wpcalibrate_lic_' . str_replace( '-', '_', $this->plugin_slug );
	}
}
