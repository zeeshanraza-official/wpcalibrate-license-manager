<?php
/**
 * License Validation Service.
 *
 * @package WPCalibrate\LicenseManager\Licensing
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Licensing;

use WPCalibrate\LicenseManager\Database\Repositories\ActivationRepository;
use WPCalibrate\LicenseManager\Database\Repositories\LicenseRepository;
use WPCalibrate\LicenseManager\Database\Repositories\ProductRepository;
use WPCalibrate\LicenseManager\Database\Repositories\VersionRepository;
use WPCalibrate\LicenseManager\Logging\AuditLogger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ValidationService
 */
final class ValidationService {

	private LicenseRepository $license_repo;
	private ActivationRepository $activation_repo;
	private ProductRepository $product_repo;
	private AuditLogger $logger;

	public function __construct(
		LicenseRepository $license_repo,
		ActivationRepository $activation_repo,
		ProductRepository $product_repo,
		AuditLogger $logger
	) {
		$this->license_repo    = $license_repo;
		$this->activation_repo = $activation_repo;
		$this->product_repo    = $product_repo;
		$this->logger          = $logger;
	}

	/**
	 * Validate license and site status.
	 *
	 * @param string $raw_key
	 * @param int    $product_id
	 * @param array  $site_data
	 * @return array
	 */
	public function validate( string $raw_key, int $product_id, array $site_data ): array {
		$hash    = KeyGenerator::hash_key( $raw_key );
		$license = $this->license_repo->find_by_hash( $hash );

		if ( ! $license ) {
			return array(
				'valid'   => false,
				'status'  => 'invalid',
				'message' => __( 'License key not found.', 'wpcalibrate-license-manager' ),
			);
		}

		if ( $license->product_id !== $product_id ) {
			return array(
				'valid'   => false,
				'status'  => 'product_mismatch',
				'message' => __( 'License key does not match this product.', 'wpcalibrate-license-manager' ),
			);
		}

		$product = $this->product_repo->find_by_id( $product_id );
		if ( ! $product ) {
			return array(
				'valid'   => false,
				'status'  => 'product_not_found',
				'message' => __( 'Product not found.', 'wpcalibrate-license-manager' ),
			);
		}

		// Check explicit non-active statuses.
		if ( in_array( $license->status, array( 'revoked', 'suspended', 'disabled' ), true ) ) {
			return array(
				'valid'            => false,
				'status'           => $license->status,
				'message'          => sprintf(
					/* translators: %s: License status */
					__( 'License is currently %s.', 'wpcalibrate-license-manager' ),
					$license->status
				),
				'is_lifetime'      => $license->is_lifetime,
				'expires_at'       => $license->expires_at,
				'activation_limit' => $license->activation_limit,
				'activation_count' => $license->activation_count,
			);
		}

		// Check expiry.
		if ( $license->is_expired() ) {
			return array(
				'valid'            => false,
				'status'           => 'expired',
				'message'          => __( 'License has expired.', 'wpcalibrate-license-manager' ),
				'is_lifetime'      => false,
				'expires_at'       => $license->expires_at,
				'renewal_url'      => $product->renewal_url ?: 'https://marketplace.wpcalibrate.com/',
				'activation_limit' => $license->activation_limit,
				'activation_count' => $license->activation_count,
			);
		}

		// Site identity check.
		$site_identifier = sanitize_text_field( (string) ( $site_data['site_identifier'] ?? '' ) );
		$site_activation = null;

		if ( ! empty( $site_identifier ) ) {
			$site_activation = $this->activation_repo->find_by_license_and_site( (int) $license->id, $site_identifier );
			if ( $site_activation ) {
				$site_activation->last_validated_at = current_time( 'mysql', true );
				$this->activation_repo->update( $site_activation );
			}
		}

		$is_site_active = null !== $site_activation;

		return array(
			'valid'            => true,
			'status'           => 'active',
			'site_active'      => $is_site_active,
			'message'          => __( 'License is active and valid.', 'wpcalibrate-license-manager' ),
			'is_lifetime'      => $license->is_lifetime,
			'expires_at'       => $license->expires_at,
			'activation_limit' => $license->activation_limit,
			'activation_count' => $license->activation_count,
			'product_name'     => $product->name,
			'current_version'  => $product->current_version,
		);
	}
}
