<?php
/**
 * Admin Form Actions Processor.
 *
 * @package WPCalibrate\LicenseManager\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Admin;

use WPCalibrate\LicenseManager\Database\Repositories\ActivationRepository;
use WPCalibrate\LicenseManager\Database\Repositories\LicenseRepository;
use WPCalibrate\LicenseManager\Database\Repositories\ProductRepository;
use WPCalibrate\LicenseManager\Database\Repositories\VersionRepository;
use WPCalibrate\LicenseManager\Domain\PluginVersion;
use WPCalibrate\LicenseManager\Domain\Product;
use WPCalibrate\LicenseManager\Licensing\ActivationService;
use WPCalibrate\LicenseManager\Licensing\LicenseService;
use WPCalibrate\LicenseManager\Logging\AuditLogger;
use WPCalibrate\LicenseManager\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ActionHandler
 */
final class ActionHandler {

	private ProductRepository $product_repo;
	private LicenseRepository $license_repo;
	private ActivationRepository $activation_repo;
	private VersionRepository $version_repo;
	private LicenseService $license_service;
	private ActivationService $activation_service;
	private AuditLogger $logger;

	public function __construct(
		ProductRepository $product_repo,
		LicenseRepository $license_repo,
		ActivationRepository $activation_repo,
		VersionRepository $version_repo,
		LicenseService $license_service,
		ActivationService $activation_service,
		AuditLogger $logger
	) {
		$this->product_repo       = $product_repo;
		$this->license_repo       = $license_repo;
		$this->activation_repo    = $activation_repo;
		$this->version_repo       = $version_repo;
		$this->license_service    = $license_service;
		$this->activation_service = $activation_service;
		$this->logger             = $logger;
	}

	public function handle(): void {
		if ( empty( $_POST['wpcalibrate_lm_action'] ) ) {
			return;
		}

		check_admin_referer( 'wpcalibrate_lm_action_nonce', 'wpcalibrate_lm_nonce' );

		$action = sanitize_key( $_POST['wpcalibrate_lm_action'] );

		switch ( $action ) {
			case 'save_product':
				$this->save_product();
				break;
			case 'delete_product':
				$this->delete_product();
				break;
			case 'create_license':
				$this->create_license();
				break;
			case 'update_license_status':
				$this->update_license_status();
				break;
			case 'extend_license':
				$this->extend_license();
				break;
			case 'reset_activations':
				$this->reset_activations();
				break;
			case 'deactivate_site':
				$this->deactivate_site();
				break;
			case 'save_release':
				$this->save_release();
				break;
			case 'save_settings':
				$this->save_settings();
				break;
		}
	}

	private function save_product(): void {
		$id   = ! empty( $_POST['product_id'] ) ? (int) $_POST['product_id'] : null;
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$slug = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );

		if ( empty( $name ) || empty( $slug ) ) {
			add_settings_error( 'wpcalibrate_lm', 'missing_fields', __( 'Product name and slug are required.', 'wpcalibrate-license-manager' ), 'error' );
			return;
		}

		// Ensure slug uniqueness.
		$existing = $this->product_repo->find_by_slug( $slug );
		if ( $existing && ( null === $id || $existing->id !== $id ) ) {
			add_settings_error( 'wpcalibrate_lm', 'duplicate_slug', __( 'A product with this slug already exists.', 'wpcalibrate-license-manager' ), 'error' );
			return;
		}

		$product = new Product(
			array(
				'id'                => $id,
				'name'              => $name,
				'slug'              => $slug,
				'current_version'   => sanitize_text_field( wp_unslash( $_POST['current_version'] ?? '1.0.0' ) ),
				'min_wp_version'    => sanitize_text_field( wp_unslash( $_POST['min_wp_version'] ?? '6.5' ) ),
				'tested_wp_version' => sanitize_text_field( wp_unslash( $_POST['tested_wp_version'] ?? '7.1.3' ) ),
				'min_php_version'   => sanitize_text_field( wp_unslash( $_POST['min_php_version'] ?? '8.2' ) ),
				'requires_wc'       => ! empty( $_POST['requires_wc'] ),
				'wc_product_id'     => ! empty( $_POST['wc_product_id'] ) ? (int) $_POST['wc_product_id'] : null,
				'renewal_url'       => esc_url_raw( wp_unslash( $_POST['renewal_url'] ?? '' ) ),
				'changelog'         => wp_kses_post( wp_unslash( $_POST['changelog'] ?? '' ) ),
				'status'            => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) ),
			)
		);

		if ( $id ) {
			$this->product_repo->update( $product );
			add_settings_error( 'wpcalibrate_lm', 'product_updated', __( 'Product updated successfully.', 'wpcalibrate-license-manager' ), 'success' );
		} else {
			$this->product_repo->insert( $product );
			add_settings_error( 'wpcalibrate_lm', 'product_created', __( 'Product created successfully.', 'wpcalibrate-license-manager' ), 'success' );
		}
	}

	private function delete_product(): void {
		$id = (int) ( $_POST['product_id'] ?? 0 );
		if ( $id > 0 ) {
			$this->product_repo->delete( $id );
			add_settings_error( 'wpcalibrate_lm', 'product_deleted', __( 'Product deleted.', 'wpcalibrate-license-manager' ), 'success' );
		}
	}

	private function create_license(): void {
		$product_id = (int) ( $_POST['product_id'] ?? 0 );
		if ( $product_id <= 0 ) {
			add_settings_error( 'wpcalibrate_lm', 'invalid_product', __( 'Please select a valid product.', 'wpcalibrate-license-manager' ), 'error' );
			return;
		}

		$params = array(
			'product_id'       => $product_id,
			'customer_email'   => sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) ),
			'activation_limit' => max( 0, (int) ( $_POST['activation_limit'] ?? 1 ) ),
			'is_lifetime'      => ! empty( $_POST['is_lifetime'] ),
			'admin_notes'      => sanitize_textarea_field( wp_unslash( $_POST['admin_notes'] ?? '' ) ),
		);

		if ( ! empty( $_POST['expires_at'] ) && empty( $params['is_lifetime'] ) ) {
			$params['expires_at'] = sanitize_text_field( wp_unslash( $_POST['expires_at'] ) );
		}

		$result = $this->license_service->create_license( $params );

		set_transient( 'wpcalibrate_lm_last_created_key_' . get_current_user_id(), $result['plaintext_key'], 300 );
		add_settings_error(
			'wpcalibrate_lm',
			'license_created',
			sprintf(
				/* translators: %s: Generated plaintext license key */
				__( 'License created successfully! Plaintext Key: %s (Copy it now, it will not be displayed in full again)', 'wpcalibrate-license-manager' ),
				'<code>' . esc_html( $result['plaintext_key'] ) . '</code>'
			),
			'success'
		);
	}

	private function update_license_status(): void {
		$license_id = (int) ( $_POST['license_id'] ?? 0 );
		$status     = sanitize_key( $_POST['status'] ?? '' );
		$reason     = sanitize_text_field( wp_unslash( $_POST['reason'] ?? 'Manual admin change' ) );

		if ( in_array( $status, array( 'active', 'suspended', 'revoked', 'expired', 'disabled' ), true ) ) {
			$this->license_service->change_status( $license_id, $status, $reason );
			add_settings_error( 'wpcalibrate_lm', 'status_updated', __( 'License status updated.', 'wpcalibrate-license-manager' ), 'success' );
		}
	}

	private function extend_license(): void {
		$license_id = (int) ( $_POST['license_id'] ?? 0 );
		$days       = max( 1, (int) ( $_POST['days'] ?? 365 ) );

		if ( $this->license_service->extend_expiry( $license_id, $days ) ) {
			add_settings_error( 'wpcalibrate_lm', 'license_extended', sprintf( __( 'License expiry extended by %d days.', 'wpcalibrate-license-manager' ), $days ), 'success' );
		} else {
			add_settings_error( 'wpcalibrate_lm', 'extend_failed', __( 'Could not extend license (may be lifetime or invalid).', 'wpcalibrate-license-manager' ), 'error' );
		}
	}

	private function reset_activations(): void {
		$license_id = (int) ( $_POST['license_id'] ?? 0 );
		if ( $license_id > 0 ) {
			$count = $this->activation_service->reset_activations( $license_id );
			add_settings_error( 'wpcalibrate_lm', 'activations_reset', sprintf( __( 'Reset %d activations for this license.', 'wpcalibrate-license-manager' ), $count ), 'success' );
		}
	}

	private function deactivate_site(): void {
		$activation_id = (int) ( $_POST['activation_id'] ?? 0 );
		$activation    = $this->activation_repo->find_by_id( $activation_id );
		if ( $activation ) {
			$this->activation_repo->delete( $activation_id );
			$this->license_repo->recalculate_activation_count( $activation->license_id );
			add_settings_error( 'wpcalibrate_lm', 'site_deactivated', __( 'Site deactivated and seat released.', 'wpcalibrate-license-manager' ), 'success' );
		}
	}

	private function save_release(): void {
		$product_id = (int) ( $_POST['product_id'] ?? 0 );
		$version    = sanitize_text_field( wp_unslash( $_POST['version'] ?? '' ) );

		if ( $product_id <= 0 || empty( $version ) ) {
			add_settings_error( 'wpcalibrate_lm', 'invalid_release', __( 'Product and version are required.', 'wpcalibrate-license-manager' ), 'error' );
			return;
		}

		$package_path = '';
		$package_size = 0;
		$package_hash = '';

		// Handle file upload if present.
		if ( ! empty( $_FILES['package_zip']['name'] ) ) {
			$uploaded_file = $_FILES['package_zip']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! str_ends_with( strtolower( (string) $uploaded_file['name'] ), '.zip' ) ) {
				add_settings_error( 'wpcalibrate_lm', 'invalid_zip', __( 'Package must be a valid .zip file.', 'wpcalibrate-license-manager' ), 'error' );
				return;
			}

			$upload_dir = wp_upload_dir();
			$target_dir = $upload_dir['basedir'] . '/wpcalibrate-packages';
			if ( ! file_exists( $target_dir ) ) {
				wp_mkdir_p( $target_dir );
				file_put_contents( $target_dir . '/index.php', '<?php // Silence is golden' );
				file_put_contents( $target_dir . '/.htaccess', 'deny from all' );
			}

			$filename    = sanitize_file_name( $uploaded_file['name'] );
			$target_file = $target_dir . '/' . $filename;

			if ( move_uploaded_file( $uploaded_file['tmp_name'], $target_file ) ) {
				$package_path = $target_file;
				$package_size = (int) filesize( $target_file );
				$package_hash = hash_file( 'sha256', $target_file );
			}
		} elseif ( ! empty( $_POST['existing_package_path'] ) ) {
			$package_path = sanitize_text_field( wp_unslash( $_POST['existing_package_path'] ) );
			if ( file_exists( $package_path ) ) {
				$package_size = (int) filesize( $package_path );
				$package_hash = hash_file( 'sha256', $package_path );
			}
		}

		$release = new PluginVersion(
			array(
				'product_id'        => $product_id,
				'version'           => $version,
				'package_path'      => $package_path,
				'package_size'      => $package_size,
				'package_hash'      => $package_hash,
				'min_wp_version'    => sanitize_text_field( wp_unslash( $_POST['min_wp_version'] ?? '6.5' ) ),
				'tested_wp_version' => sanitize_text_field( wp_unslash( $_POST['tested_wp_version'] ?? '7.1.3' ) ),
				'min_php_version'   => sanitize_text_field( wp_unslash( $_POST['min_php_version'] ?? '8.2' ) ),
				'changelog'         => wp_kses_post( wp_unslash( $_POST['changelog'] ?? '' ) ),
				'upgrade_notice'    => sanitize_textarea_field( wp_unslash( $_POST['upgrade_notice'] ?? '' ) ),
				'status'            => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'published' ) ),
			)
		);

		$existing = $this->version_repo->find_by_product_and_version( $product_id, $version );
		if ( $existing ) {
			$release->id = $existing->id;
			$this->version_repo->update( $release );
			add_settings_error( 'wpcalibrate_lm', 'release_updated', __( 'Release updated.', 'wpcalibrate-license-manager' ), 'success' );
		} else {
			$this->version_repo->insert( $release );
			add_settings_error( 'wpcalibrate_lm', 'release_created', __( 'Release created.', 'wpcalibrate-license-manager' ), 'success' );
		}

		// Update product current_version if newer.
		$product = $this->product_repo->find_by_id( $product_id );
		if ( $product && version_compare( $version, $product->current_version, '>' ) ) {
			$product->current_version = $version;
			$this->product_repo->update( $product );
		}
	}

	private function save_settings(): void {
		$settings = array(
			'validation_cache_interval_hours' => max( 1, (int) ( $_POST['validation_cache_interval_hours'] ?? 24 ) ),
			'grace_period_days'              => max( 1, (int) ( $_POST['grace_period_days'] ?? 7 ) ),
			'default_activation_limit'        => max( 0, (int) ( $_POST['default_activation_limit'] ?? 1 ) ),
			'default_expiry_days'             => max( 1, (int) ( $_POST['default_expiry_days'] ?? 365 ) ),
			'staging_patterns'                => sanitize_textarea_field( wp_unslash( $_POST['staging_patterns'] ?? '' ) ),
			'download_token_ttl_seconds'      => max( 60, (int) ( $_POST['download_token_ttl_seconds'] ?? 900 ) ),
			'logging_level'                   => sanitize_text_field( wp_unslash( $_POST['logging_level'] ?? 'info' ) ),
			'data_retention_policy'           => sanitize_text_field( wp_unslash( $_POST['data_retention_policy'] ?? 'preserve' ) ),
			'log_retention_days'              => max( 7, (int) ( $_POST['log_retention_days'] ?? 90 ) ),
			'wc_auto_license'                 => ! empty( $_POST['wc_auto_license'] ) ? 1 : 0,
			'wc_refund_action'                => sanitize_text_field( wp_unslash( $_POST['wc_refund_action'] ?? 'suspend' ) ),
			'rate_limit_requests'             => max( 10, (int) ( $_POST['rate_limit_requests'] ?? 60 ) ),
			'rate_limit_window_seconds'       => max( 10, (int) ( $_POST['rate_limit_window_seconds'] ?? 60 ) ),
		);

		update_option( 'wpcalibrate_lm_settings', $settings, false );
		update_option( 'wpcalibrate_lm_data_retention_policy', $settings['data_retention_policy'], false );

		add_settings_error( 'wpcalibrate_lm', 'settings_saved', __( 'Settings saved successfully.', 'wpcalibrate-license-manager' ), 'success' );
	}
}
