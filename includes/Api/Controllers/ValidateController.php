<?php
/**
 * REST Controller for License Validation.
 *
 * @package WPCalibrate\LicenseManager\Api\Controllers
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Api\Controllers;

use WPCalibrate\LicenseManager\Api\RateLimiter;
use WPCalibrate\LicenseManager\Licensing\ValidationService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ValidateController
 */
final class ValidateController {

	private ValidationService $validation_service;

	public function __construct( ValidationService $validation_service ) {
		$this->validation_service = $validation_service;
	}

	public function register_routes( string $namespace ): void {
		register_rest_route(
			$namespace,
			'/validate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_validate' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'license_key'     => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'product_id'      => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'site_identifier' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'site_url'        => array(
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

	public function handle_validate( WP_REST_Request $request ): WP_REST_Response {
		$license_key = (string) $request->get_param( 'license_key' );
		$product_id  = (int) $request->get_param( 'product_id' );

		$site_data = array(
			'site_identifier' => (string) ( $request->get_param( 'site_identifier' ) ?? '' ),
			'site_url'        => (string) ( $request->get_param( 'site_url' ) ?? '' ),
		);

		$result = $this->validation_service->validate( $license_key, $product_id, $site_data );

		return new WP_REST_Response(
			array(
				'api_version' => '1.0.0',
				'server_time' => gmdate( 'c' ),
				'data'        => $result,
			),
			200
		);
	}
}
