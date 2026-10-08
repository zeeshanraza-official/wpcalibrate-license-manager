<?php
/**
 * SDK License Status Constants.
 *
 * @package WPCalibrate\LicensingSDK\License
 */

declare(strict_types=1);

namespace WPCalibrate\LicensingSDK\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Status
 */
final class Status {

	public const ACTIVE            = 'active';
	public const EXPIRED           = 'expired';
	public const SUSPENDED         = 'suspended';
	public const REVOKED           = 'revoked';
	public const DISABLED          = 'disabled';
	public const INVALID           = 'invalid';
	public const UNLICENSED        = 'unlicensed';
	public const NETWORK_ERROR     = 'network_error';
	public const GRACE_PERIOD      = 'grace_period';
}
