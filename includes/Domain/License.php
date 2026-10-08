<?php
/**
 * License Domain Model.
 *
 * @package WPCalibrate\LicenseManager\Domain
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class License
 */
final class License {

	public ?int $id;
	public string $license_key_hash;
	public string $license_key_masked;
	public ?string $license_key_encrypted;
	public int $product_id;
	public ?int $customer_id;
	public string $customer_email;
	public ?int $order_id;
	public ?int $subscription_id;
	public int $activation_limit;
	public int $activation_count;
	public bool $is_lifetime;
	public ?string $expires_at;
	public string $status;
	public ?string $admin_notes;
	public ?string $metadata;
	public string $created_at;
	public string $updated_at;

	public function __construct( array $data = array() ) {
		$this->id                   = isset( $data['id'] ) ? (int) $data['id'] : null;
		$this->license_key_hash     = (string) ( $data['license_key_hash'] ?? '' );
		$this->license_key_masked   = (string) ( $data['license_key_masked'] ?? '' );
		$this->license_key_encrypted = isset( $data['license_key_encrypted'] ) ? (string) $data['license_key_encrypted'] : null;
		$this->product_id           = (int) ( $data['product_id'] ?? 0 );
		$this->customer_id         = ! empty( $data['customer_id'] ) ? (int) $data['customer_id'] : null;
		$this->customer_email       = sanitize_email( (string) ( $data['customer_email'] ?? '' ) );
		$this->order_id             = ! empty( $data['order_id'] ) ? (int) $data['order_id'] : null;
		$this->subscription_id      = ! empty( $data['subscription_id'] ) ? (int) $data['subscription_id'] : null;
		$this->activation_limit     = (int) ( $data['activation_limit'] ?? 1 );
		$this->activation_count     = (int) ( $data['activation_count'] ?? 0 );
		$this->is_lifetime          = ! empty( $data['is_lifetime'] );
		$this->expires_at           = ! empty( $data['expires_at'] ) ? (string) $data['expires_at'] : null;
		$this->status               = (string) ( $data['status'] ?? 'active' );
		$this->admin_notes          = isset( $data['admin_notes'] ) ? (string) $data['admin_notes'] : null;
		$this->metadata             = isset( $data['metadata'] ) ? (string) $data['metadata'] : null;
		$this->created_at           = (string) ( $data['created_at'] ?? current_time( 'mysql', true ) );
		$this->updated_at           = (string) ( $data['updated_at'] ?? current_time( 'mysql', true ) );
	}

	public function is_active(): bool {
		if ( 'active' !== $this->status ) {
			return false;
		}

		if ( ! $this->is_lifetime && ! empty( $this->expires_at ) ) {
			if ( strtotime( $this->expires_at ) < time() ) {
				return false;
			}
		}

		return true;
	}

	public function is_expired(): bool {
		if ( 'expired' === $this->status ) {
			return true;
		}

		if ( ! $this->is_lifetime && ! empty( $this->expires_at ) ) {
			return strtotime( $this->expires_at ) < time();
		}

		return false;
	}

	public function has_available_seats(): bool {
		// 0 represents unlimited seats.
		if ( 0 === $this->activation_limit ) {
			return true;
		}

		return $this->activation_count < $this->activation_limit;
	}

	public function to_array(): array {
		return array(
			'id'                   => $this->id,
			'license_key_hash'     => $this->license_key_hash,
			'license_key_masked'   => $this->license_key_masked,
			'license_key_encrypted' => $this->license_key_encrypted,
			'product_id'           => $this->product_id,
			'customer_id'         => $this->customer_id,
			'customer_email'       => $this->customer_email,
			'order_id'             => $this->order_id,
			'subscription_id'      => $this->subscription_id,
			'activation_limit'     => $this->activation_limit,
			'activation_count'     => $this->activation_count,
			'is_lifetime'          => $this->is_lifetime ? 1 : 0,
			'expires_at'           => $this->expires_at,
			'status'               => $this->status,
			'admin_notes'          => $this->admin_notes,
			'metadata'             => $this->metadata,
			'created_at'           => $this->created_at,
			'updated_at'           => $this->updated_at,
		);
	}
}
