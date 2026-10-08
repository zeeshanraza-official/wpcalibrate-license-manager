<?php
/**
 * REST Controller for License Activation.
 *
 * @package WPCalibrate\LicenseManager\Api\Controllers
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Api\Controllers;

use WPCalibrate\LicenseManager\Api\RateLimiter;
use WPCalibrate\LicenseManager\Licensing\ActivationService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ActivateController
 */
final class ActivateController {

	private ActivationService $activation_service;

	public function __construct( ActivationService $activation_service ) {
		$this->activation_service = $activation_service;
	}

	public function register_routes( string $namespace ): void {
		register_rest_route(
			$namespace,
			'/activate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_activate' ),
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
					'site_url'        => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
					'site_identifier' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'wp_version'      => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'plugin_version'  => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'multisite'       => array(
						'required'          => false,
						'type'              => 'boolean',
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

	public function handle_activate( WP_REST_Request $request ): WP_REST_Response {
		$license_key = (string) $request->get_param( 'license_key' );
		$product_id  = (int) $request->get_param( 'product_id' );

		$site_data = array(
			'site_url'        => (string) $request->get_param( 'site_url' ),
			'site_identifier' => (string) ( $request->get_param( 'site_identifier' ) ?? '' ),
			'wp_version'      => (string) ( $request->get_param( 'wp_version' ) ?? '' ),
			'plugin_version'  => (string) ( $request->get_param( 'plugin_version' ) ?? '' ),
			'multisite'       => (bool) $request->get_param( 'multisite' ),
			'site_ip'         => RateLimiter::get_client_ip(),
		);

		$result = $this->activation_service->activate( $license_key, $product_id, $site_data );

		$status_code = $result['success'] ? 200 : 400;
		if ( ! $result['success'] && in_array( $result['code'], array( 'invalid_key', 'product_mismatch' ), true ) ) {
			$status_code = 404;
		}

		return new WP_REST_Response(
			array(
				'api_version' => '1.0.0',
				'server_time' => gmdate( 'c' ),
				'data'        => $result,
			),
			$status_code
		);
	}
}
