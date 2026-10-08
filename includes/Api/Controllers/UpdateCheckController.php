<?php
/**
 * REST Controller for Private Update Checks.
 *
 * @package WPCalibrate\LicenseManager\Api\Controllers
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Api\Controllers;

use WPCalibrate\LicenseManager\Api\RateLimiter;
use WPCalibrate\LicenseManager\Database\Repositories\ProductRepository;
use WPCalibrate\LicenseManager\Database\Repositories\VersionRepository;
use WPCalibrate\LicenseManager\Downloads\PackageDownloadService;
use WPCalibrate\LicenseManager\Licensing\ValidationService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UpdateCheckController
 */
final class UpdateCheckController {

	private VersionRepository $version_repo;
	private ProductRepository $product_repo;
	private ValidationService $validation_service;
	private PackageDownloadService $download_service;

	public function __construct(
		VersionRepository $version_repo,
		ProductRepository $product_repo,
		ValidationService $validation_service,
		PackageDownloadService $download_service
	) {
		$this->version_repo       = $version_repo;
		$this->product_repo       = $product_repo;
		$this->validation_service = $validation_service;
		$this->download_service   = $download_service;
	}

	public function register_routes( string $namespace ): void {
		register_rest_route(
			$namespace,
			'/update-check',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_update_check' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'product_id'        => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'plugin_slug'       => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_title',
					),
					'installed_version' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'license_key'       => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'site_identifier'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'site_url'          => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);
	}

	public function check_permissions( WP_REST_Request $request ): true|WP_Error {
		$ip = RateLimiter::get_client_ip();
		if ( ! RateLimiter::check( $ip ) ) {
			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Too many requests. Please try again later.', 'wpcalibrate-license-manager' ),
				array( 'status' => 429 )
			);
		}

		return true;
	}

	public function handle_update_check( WP_REST_Request $request ): WP_REST_Response {
		$product_id        = (int) $request->get_param( 'product_id' );
		$installed_version = (string) $request->get_param( 'installed_version' );
		$license_key       = (string) ( $request->get_param( 'license_key' ) ?? '' );
		$site_identifier   = (string) ( $request->get_param( 'site_identifier' ) ?? '' );
		$site_url          = (string) ( $request->get_param( 'site_url' ) ?? '' );

		$product = $this->product_repo->find_by_id( $product_id );
		if ( ! $product ) {
			return new WP_REST_Response(
				array(
					'api_version' => '1.0.0',
					'error'       => 'product_not_found',
					'message'     => __( 'Product not found.', 'wpcalibrate-license-manager' ),
				),
				404
			);
		}

		$latest = $this->version_repo->get_latest_release( $product_id );
		if ( ! $latest ) {
			return new WP_REST_Response(
				array(
					'api_version' => '1.0.0',
					'has_update'  => false,
					'message'     => __( 'No releases found.', 'wpcalibrate-license-manager' ),
				),
				200
			);
		}

		$has_update = version_compare( $latest->version, $installed_version, '>' );

		$package_url       = '';
		$is_license_active = false;
		$license_status    = 'no_license';

		if ( ! empty( $license_key ) ) {
			$val_result = $this->validation_service->validate(
				$license_key,
				$product_id,
				array(
					'site_identifier' => $site_identifier,
					'site_url'        => $site_url,
				)
			);

			$license_status = $val_result['status'] ?? 'invalid';

			if ( ! empty( $val_result['valid'] ) && 'active' === $license_status ) {
				$is_license_active = true;

				// Generate authorized short-lived token download URL if update is available.
				if ( $has_update ) {
					$token_res = $this->download_service->issue_download_token(
						$product_id,
						$latest->version,
						$license_key,
						$site_identifier
					);

					if ( ! empty( $token_res['success'] ) ) {
						$package_url = $token_res['download_url'];
					}
				}
			}
		}

		$response_data = array(
			'api_version'       => '1.0.0',
			'server_time'       => gmdate( 'c' ),
			'product_id'        => $product_id,
			'plugin_slug'       => $product->slug,
			'has_update'        => $has_update,
			'installed_version' => $installed_version,
			'latest_version'    => $latest->version,
			'requires_wp'       => $latest->min_wp_version,
			'tested_wp'         => $latest->tested_wp_version,
			'requires_php'      => $latest->min_php_version,
			'changelog'         => $latest->changelog,
			'upgrade_notice'    => $latest->upgrade_notice,
			'license_status'    => $license_status,
			'package_available' => ! empty( $package_url ),
			'package'           => $package_url, // Never contains permanent license keys!
			'renewal_url'       => $product->renewal_url ?: 'https://marketplace.wpcalibrate.com/',
		);

		return new WP_REST_Response( $response_data, 200 );
	}
}
