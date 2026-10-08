<?php
/**
 * Activation Domain Model.
 *
 * @package WPCalibrate\LicenseManager\Domain
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Activation
 */
final class Activation {

	public ?int $id;
	public int $license_id;
	public int $product_id;
	public string $site_identifier;
	public string $site_url;
	public string $site_domain;
	public string $site_ip;
	public string $wp_version;
	public string $plugin_version;
	public bool $is_staging;
	public bool $multisite;
	public string $created_at;
	public string $updated_at;
	public string $last_validated_at;

	public function __construct( array $data = array() ) {
		$this->id                = isset( $data['id'] ) ? (int) $data['id'] : null;
		$this->license_id        = (int) ( $data['license_id'] ?? 0 );
		$this->product_id        = (int) ( $data['product_id'] ?? 0 );
		$this->site_identifier   = (string) ( $data['site_identifier'] ?? '' );
		$this->site_url          = esc_url_raw( (string) ( $data['site_url'] ?? '' ) );
		$this->site_domain       = sanitize_text_field( (string) ( $data['site_domain'] ?? '' ) );
		$this->site_ip           = sanitize_text_field( (string) ( $data['site_ip'] ?? '' ) );
		$this->wp_version        = sanitize_text_field( (string) ( $data['wp_version'] ?? '' ) );
		$this->plugin_version    = sanitize_text_field( (string) ( $data['plugin_version'] ?? '' ) );
		$this->is_staging        = ! empty( $data['is_staging'] );
		$this->multisite         = ! empty( $data['multisite'] );
		$this->created_at        = (string) ( $data['created_at'] ?? current_time( 'mysql', true ) );
		$this->updated_at        = (string) ( $data['updated_at'] ?? current_time( 'mysql', true ) );
		$this->last_validated_at = (string) ( $data['last_validated_at'] ?? current_time( 'mysql', true ) );
	}

	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'license_id'        => $this->license_id,
			'product_id'        => $this->product_id,
			'site_identifier'   => $this->site_identifier,
			'site_url'          => $this->site_url,
			'site_domain'       => $this->site_domain,
			'site_ip'           => $this->site_ip,
			'wp_version'        => $this->wp_version,
			'plugin_version'    => $this->plugin_version,
			'is_staging'        => $this->is_staging ? 1 : 0,
			'multisite'         => $this->multisite ? 1 : 0,
			'created_at'        => $this->created_at,
			'updated_at'        => $this->updated_at,
			'last_validated_at' => $this->last_validated_at,
		);
	}
}
