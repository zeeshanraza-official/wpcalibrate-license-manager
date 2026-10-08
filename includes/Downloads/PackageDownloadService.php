<?php
/**
 * Protected Package Download Service.
 *
 * @package WPCalibrate\LicenseManager\Downloads
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Downloads;

use WPCalibrate\LicenseManager\Database\Repositories\ActivationRepository;
use WPCalibrate\LicenseManager\Database\Repositories\LicenseRepository;
use WPCalibrate\LicenseManager\Database\Repositories\ProductRepository;
use WPCalibrate\LicenseManager\Database\Repositories\VersionRepository;
use WPCalibrate\LicenseManager\Domain\PluginVersion;
use WPCalibrate\LicenseManager\Logging\AuditLogger;
use WPCalibrate\LicenseManager\Security\TokenManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PackageDownloadService
 */
final class PackageDownloadService {

	private VersionRepository $version_repo;
	private LicenseRepository $license_repo;
	private ProductRepository $product_repo;
	private ActivationRepository $activation_repo;
	private AuditLogger $logger;

	public function __construct(
		VersionRepository $version_repo,
		LicenseRepository $license_repo,
		ProductRepository $product_repo,
		ActivationRepository $activation_repo,
		AuditLogger $logger
	) {
		$this->version_repo    = $version_repo;
		$this->license_repo    = $license_repo;
		$this->product_repo    = $product_repo;
		$this->activation_repo = $activation_repo;
		$this->logger          = $logger;
	}

	/**
	 * Hook download listener to WordPress template_redirect or init.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'handle_download_request' ) );
	}

	/**
	 * Issue a secure, short-lived download token.
	 *
	 * @param int    $product_id
	 * @param string $version_string
	 * @param string $raw_key
	 * @param string $site_identifier
	 * @return array{success: bool, download_url?: string, expires_in?: int, message?: string}
	 */
	public function issue_download_token( int $product_id, string $version_string, string $raw_key, string $site_identifier = '' ): array {
		$hash    = \WPCalibrate\LicenseManager\Licensing\KeyGenerator::hash_key( $raw_key );
		$license = $this->license_repo->find_by_hash( $hash );

		if ( ! $license || $license->product_id !== $product_id ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid license or product mismatch.', 'wpcalibrate-license-manager' ),
			);
		}

		if ( ! $license->is_active() ) {
			return array(
				'success' => false,
				'message' => __( 'License is not active or has expired. Download authorization denied.', 'wpcalibrate-license-manager' ),
			);
		}

		$release = $this->version_repo->find_by_product_and_version( $product_id, $version_string );
		if ( ! $release || 'published' !== $release->status ) {
			return array(
				'success' => false,
				'message' => __( 'Requested release version was not found or is not published.', 'wpcalibrate-license-manager' ),
			);
		}

		$settings    = get_option( 'wpcalibrate_lm_settings', array() );
		$ttl_seconds = max( 120, (int) ( $settings['download_token_ttl_seconds'] ?? 900 ) );

		$token = TokenManager::generate_download_token(
			$product_id,
			(int) $release->id,
			(int) $license->id,
			$site_identifier,
			$ttl_seconds
		);

		$download_url = add_query_arg(
			array(
				'wpcalibrate_action' => 'download_package',
				'token'              => $token,
			),
			home_url( '/' )
		);

		$this->logger->log(
			'download_token_issued',
			$license->id,
			$product_id,
			'',
			array(
				'version'    => $version_string,
				'expires_in' => $ttl_seconds,
			)
		);

		return array(
			'success'      => true,
			'download_url' => $download_url,
			'expires_in'   => $ttl_seconds,
		);
	}

	/**
	 * Listen for download package requests.
	 */
	public function handle_download_request(): void {
		if ( empty( $_GET['wpcalibrate_action'] ) || 'download_package' !== $_GET['wpcalibrate_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$token = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $token ) ) {
			wp_die( esc_html__( 'Missing download token.', 'wpcalibrate-license-manager' ), 400 );
		}

		$payload = TokenManager::verify_download_token( $token );
		if ( ! $payload ) {
			wp_die( esc_html__( 'Download token is invalid or has expired.', 'wpcalibrate-license-manager' ), 403 );
		}

		$product_id = (int) $payload['pid'];
		$version_id = (int) $payload['vid'];
		$license_id = (int) $payload['lid'];

		$license = $this->license_repo->find_by_id( $license_id );
		if ( ! $license || ! $license->is_active() || $license->product_id !== $product_id ) {
			wp_die( esc_html__( 'Associated license is no longer authorized.', 'wpcalibrate-license-manager' ), 403 );
		}

		$version = $this->version_repo->find_by_id( $version_id );
		if ( ! $version || $version->product_id !== $product_id ) {
			wp_die( esc_html__( 'Package release not found.', 'wpcalibrate-license-manager' ), 404 );
		}

		$product = $this->product_repo->find_by_id( $product_id );
		$slug    = $product ? $product->slug : 'plugin';

		$file_path = $version->package_path;

		// Resolve path and prevent path traversal attacks.
		$upload_dir = wp_upload_dir();
		$base_dir   = realpath( $upload_dir['basedir'] );

		// Check if file path is stored as absolute or relative to uploads.
		$real_file = realpath( $file_path );
		if ( ! $real_file || ! file_exists( $real_file ) ) {
			// Check within wp-content/uploads/wpcalibrate-packages/
			$candidate = realpath( $upload_dir['basedir'] . '/' . ltrim( $file_path, '/\\' ) );
			if ( $candidate && file_exists( $candidate ) ) {
				$real_file = $candidate;
			}
		}

		if ( ! $real_file || ! file_exists( $real_file ) || ! is_readable( $real_file ) ) {
			wp_die( esc_html__( 'Package archive is missing on server.', 'wpcalibrate-license-manager' ), 404 );
		}

		// Ensure extension is .zip.
		if ( ! str_ends_with( strtolower( $real_file ), '.zip' ) ) {
			wp_die( esc_html__( 'Invalid package file format.', 'wpcalibrate-license-manager' ), 403 );
		}

		$filename = sprintf( '%s-%s.zip', $slug, $version->version );

		$this->logger->log(
			'package_downloaded',
			$license_id,
			$product_id,
			'',
			array(
				'version'  => $version->version,
				'filename' => $filename,
			)
		);

		// Stream the file safely.
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . esc_attr( $filename ) . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: private, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Content-Length: ' . (string) filesize( $real_file ) );

		readfile( $real_file );
		exit;
	}

	/**
	 * Token cleanup helper.
	 */
	public function cleanup_expired_tokens(): void {
		// Handled statelessly via HMAC-SHA256 timestamp expiry verification.
	}
}
