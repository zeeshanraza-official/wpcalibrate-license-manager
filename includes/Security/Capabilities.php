<?php
/**
 * Role & Capability Definitions.
 *
 * @package WPCalibrate\LicenseManager\Security
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Capabilities
 */
final class Capabilities {

	/**
	 * Default required capability for administrative license actions.
	 */
	public const MANAGE_LICENSES = 'manage_options';

	/**
	 * Get the required capability, filterable by site administrators.
	 *
	 * @return string
	 */
	public static function required_capability(): string {
		return apply_filters( 'wpcalibrate_lm_required_capability', self::MANAGE_LICENSES );
	}

	/**
	 * Check whether the current user has the required capability.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage(): bool {
		return current_user_can( self::required_capability() );
	}
}
