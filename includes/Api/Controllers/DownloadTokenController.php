<?php
/**
 * REST Controller for Requesting Short-Lived Download Tokens.
 *
 * @package WPCalibrate\LicenseManager\Api\Controllers
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Api\Controllers;

use WPCalibrate\LicenseManager\Api\RateLimiter;
use WPCalibrate\LicenseManager\Downloads\PackageDownloadService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DownloadTokenController
 */
final class DownloadTokenController {

	private PackageDownloadService $download_service;

	public function __construct( PackageDownloadService $download_service ) {
		$this->download_service = $download_service;
	}

	public function register_routes( string $namespace ): void {
		register_rest_route(
			$namespace,
			'/download-token',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_token_request' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'product_id'      => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'version'         => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'license_key'     => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'site_identifier' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
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

	public function handle_token_request( WP_REST_Request $request ): WP_REST_Response {
		$product_id      = (int) $request->get_param( 'product_id' );
		$version         = (string) $request->get_param( 'version' );
		$license_key     = (string) $request->get_param( 'license_key' );
		$site_identifier = (string) ( $request->get_param( 'site_identifier' ) ?? '' );

		$result = $this->download_service->issue_download_token(
			$product_id,
			$version,
			$license_key,
			$site_identifier
		);

		$status = $result['success'] ? 200 : 403;

		return new WP_REST_Response(
			array(
				'api_version' => '1.0.0',
				'server_time' => gmdate( 'c' ),
				'data'        => $result,
			),
			$status
		);
	}
}
