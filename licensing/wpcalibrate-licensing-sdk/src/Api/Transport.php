<?php
/**
 * SDK HTTP Transport.
 *
 * Wrapper around WordPress HTTP API with timeouts, user-agent, and safe JSON handling.
 *
 * @package WPCalibrate\LicensingSDK\Api
 */

declare(strict_types=1);

namespace WPCalibrate\LicensingSDK\Api;

use WPCalibrate\LicensingSDK\Config;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Transport
 */
final class Transport {

	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Send POST request to licensing server.
	 *
	 * @param string $endpoint Path relative to REST namespace, e.g. 'activate'.
	 * @param array  $body     Request body payload.
	 * @return array{success: bool, http_code: int, data: array, error?: string}
	 */
	public function post( string $endpoint, array $body ): array {
		$url = $this->config->get_license_server_url() . '/wp-json/wpcalibrate-license/v1/' . ltrim( $endpoint, '/' );

		$args = array(
			'timeout'     => 15,
			'redirection' => 3,
			'httpversion' => '1.1',
			'user-agent'  => sprintf(
				'WPCalibrate-SDK/1.0.0; %s/%s; WordPress/%s; %s',
				$this->config->get_plugin_slug(),
				$this->config->get_current_version(),
				get_bloginfo( 'version' ),
				home_url()
			),
			'headers'     => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
			'body'        => (string) wp_json_encode( $body ),
			'sslverify'   => apply_filters( 'wpcalibrate_sdk_sslverify', true ),
		);

		$response = wp_safe_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success'   => false,
				'http_code' => 0,
				'data'      => array(),
				'error'     => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );

		$json = json_decode( $raw, true );
		if ( ! is_array( $json ) ) {
			return array(
				'success'   => false,
				'http_code' => $code,
				'data'      => array(),
				'error'     => __( 'Malformed response received from licensing server.', 'wpcalibrate-license-manager' ),
			);
		}

		$payload_data = $json['data'] ?? $json;

		return array(
			'success'   => $code >= 200 && $code < 300,
			'http_code' => $code,
			'data'      => is_array( $payload_data ) ? $payload_data : array(),
			'error'     => $json['message'] ?? ( $payload_data['message'] ?? '' ),
		);
	}
}
