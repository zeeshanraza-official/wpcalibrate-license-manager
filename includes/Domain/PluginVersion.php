<?php
/**
 * Plugin Version (Release) Domain Model.
 *
 * @package WPCalibrate\LicenseManager\Domain
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PluginVersion
 */
final class PluginVersion {

	public ?int $id;
	public int $product_id;
	public string $version;
	public string $package_path;
	public int $package_size;
	public string $package_hash;
	public string $min_wp_version;
	public string $tested_wp_version;
	public string $min_php_version;
	public string $changelog;
	public ?string $upgrade_notice;
	public string $status;
	public string $release_date;
	public string $created_at;

	public function __construct( array $data = array() ) {
		$this->id                = isset( $data['id'] ) ? (int) $data['id'] : null;
		$this->product_id        = (int) ( $data['product_id'] ?? 0 );
		$this->version           = sanitize_text_field( (string) ( $data['version'] ?? '1.0.0' ) );
		$this->package_path      = (string) ( $data['package_path'] ?? '' );
		$this->package_size      = (int) ( $data['package_size'] ?? 0 );
		$this->package_hash      = (string) ( $data['package_hash'] ?? '' );
		$this->min_wp_version    = (string) ( $data['min_wp_version'] ?? '6.5' );
		$this->tested_wp_version = (string) ( $data['tested_wp_version'] ?? '7.1.3' );
		$this->min_php_version   = (string) ( $data['min_php_version'] ?? '8.2' );
		$this->changelog         = (string) ( $data['changelog'] ?? '' );
		$this->upgrade_notice    = isset( $data['upgrade_notice'] ) ? (string) $data['upgrade_notice'] : null;
		$this->status            = (string) ( $data['status'] ?? 'published' );
		$this->release_date      = (string) ( $data['release_date'] ?? current_time( 'mysql', true ) );
		$this->created_at        = (string) ( $data['created_at'] ?? current_time( 'mysql', true ) );
	}

	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'product_id'        => $this->product_id,
			'version'           => $this->version,
			'package_path'      => $this->package_path,
			'package_size'      => $this->package_size,
			'package_hash'      => $this->package_hash,
			'min_wp_version'    => $this->min_wp_version,
			'tested_wp_version' => $this->tested_wp_version,
			'min_php_version'   => $this->min_php_version,
			'changelog'         => $this->changelog,
			'upgrade_notice'    => $this->upgrade_notice,
			'status'            => $this->status,
			'release_date'      => $this->release_date,
			'created_at'        => $this->created_at,
		);
	}
}
