<?php
/**
 * Product Domain Model.
 *
 * @package WPCalibrate\LicenseManager\Domain
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Product
 */
final class Product {

	public ?int $id;
	public string $name;
	public string $slug;
	public string $current_version;
	public string $min_wp_version;
	public string $tested_wp_version;
	public string $min_php_version;
	public bool $requires_wc;
	public ?int $wc_product_id;
	public string $changelog;
	public string $renewal_url;
	public string $status;
	public string $created_at;
	public string $updated_at;

	public function __construct( array $data = array() ) {
		$this->id                = isset( $data['id'] ) ? (int) $data['id'] : null;
		$this->name              = (string) ( $data['name'] ?? '' );
		$this->slug              = sanitize_title( (string) ( $data['slug'] ?? '' ) );
		$this->current_version   = (string) ( $data['current_version'] ?? '1.0.0' );
		$this->min_wp_version    = (string) ( $data['min_wp_version'] ?? '6.5' );
		$this->tested_wp_version = (string) ( $data['tested_wp_version'] ?? '7.1.3' );
		$this->min_php_version   = (string) ( $data['min_php_version'] ?? '8.2' );
		$this->requires_wc       = ! empty( $data['requires_wc'] );
		$this->wc_product_id     = ! empty( $data['wc_product_id'] ) ? (int) $data['wc_product_id'] : null;
		$this->changelog         = (string) ( $data['changelog'] ?? '' );
		$this->renewal_url       = (string) ( $data['renewal_url'] ?? '' );
		$this->status            = (string) ( $data['status'] ?? 'active' );
		$this->created_at        = (string) ( $data['created_at'] ?? current_time( 'mysql', true ) );
		$this->updated_at        = (string) ( $data['updated_at'] ?? current_time( 'mysql', true ) );
	}

	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'name'              => $this->name,
			'slug'              => $this->slug,
			'current_version'   => $this->current_version,
			'min_wp_version'    => $this->min_wp_version,
			'tested_wp_version' => $this->tested_wp_version,
			'min_php_version'   => $this->min_php_version,
			'requires_wc'       => $this->requires_wc ? 1 : 0,
			'wc_product_id'     => $this->wc_product_id,
			'changelog'         => $this->changelog,
			'renewal_url'       => $this->renewal_url,
			'status'            => $this->status,
			'created_at'        => $this->created_at,
			'updated_at'        => $this->updated_at,
		);
	}
}
