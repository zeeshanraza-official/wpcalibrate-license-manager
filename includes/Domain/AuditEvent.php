<?php
/**
 * Audit Event Domain Model.
 *
 * @package WPCalibrate\LicenseManager\Domain
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AuditEvent
 */
final class AuditEvent {

	public ?int $id;
	public ?int $license_id;
	public ?int $product_id;
	public string $event_type;
	public string $site_domain;
	public string $ip_address;
	public ?int $user_id;
	public ?string $context_data;
	public string $created_at;

	public function __construct( array $data = array() ) {
		$this->id           = isset( $data['id'] ) ? (int) $data['id'] : null;
		$this->license_id   = ! empty( $data['license_id'] ) ? (int) $data['license_id'] : null;
		$this->product_id   = ! empty( $data['product_id'] ) ? (int) $data['product_id'] : null;
		$this->event_type   = sanitize_text_field( (string) ( $data['event_type'] ?? 'info' ) );
		$this->site_domain  = sanitize_text_field( (string) ( $data['site_domain'] ?? '' ) );
		$this->ip_address   = sanitize_text_field( (string) ( $data['ip_address'] ?? '' ) );
		$this->user_id      = ! empty( $data['user_id'] ) ? (int) $data['user_id'] : null;
		$this->context_data = isset( $data['context_data'] ) ? (string) $data['context_data'] : null;
		$this->created_at   = (string) ( $data['created_at'] ?? current_time( 'mysql', true ) );
	}

	public function to_array(): array {
		return array(
			'id'           => $this->id,
			'license_id'   => $this->license_id,
			'product_id'   => $this->product_id,
			'event_type'   => $this->event_type,
			'site_domain'  => $this->site_domain,
			'ip_address'   => $this->ip_address,
			'user_id'      => $this->user_id,
			'context_data' => $this->context_data,
			'created_at'   => $this->created_at,
		);
	}
}
