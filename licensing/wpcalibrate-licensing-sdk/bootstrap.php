<?php
/**
 * WPCalibrate Licensing SDK Bootstrap Loader.
 *
 * Safe for inclusion across multiple WPCalibrate plugins.
 *
 * @package WPCalibrate\LicensingSDK
 */

declare(strict_types=1);

namespace WPCalibrate\LicensingSDK;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Register SDK Autoloader if not already registered.
if ( ! class_exists( __NAMESPACE__ . '\\Client', false ) ) {
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix = 'WPCalibrate\\LicensingSDK\\';
			if ( ! str_starts_with( $class, $prefix ) ) {
				return;
			}

			$relative_class = substr( $class, strlen( $prefix ) );
			$file           = __DIR__ . '/src/' . str_replace( '\\', '/', $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}
