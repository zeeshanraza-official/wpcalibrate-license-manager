<?php
/**
 * REST API Server Coordinator.
 *
 * @package WPCalibrate\LicenseManager\Api
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Api;

use WPCalibrate\LicenseManager\Api\Controllers\ActivateController;
use WPCalibrate\LicenseManager\Api\Controllers\DeactivateController;
use WPCalibrate\LicenseManager\Api\Controllers\DownloadTokenController;
use WPCalibrate\LicenseManager\Api\Controllers\UpdateCheckController;
use WPCalibrate\LicenseManager\Api\Controllers\ValidateController;
use WPCalibrate\LicenseManager\Database\Repositories\ProductRepository;
use WPCalibrate\LicenseManager\Database\Repositories\VersionRepository;
use WPCalibrate\LicenseManager\Downloads\PackageDownloadService;
use WPCalibrate\LicenseManager\Licensing\ActivationService;
use WPCalibrate\LicenseManager\Licensing\LicenseService;
use WPCalibrate\LicenseManager\Licensing\ValidationService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RestServer
 */
final class RestServer {

	public const NAMESPACE = 'wpcalibrate-license/v1';

	private ActivateController $activate_controller;
	private DeactivateController $deactivate_controller;
	private ValidateController $validate_controller;
	private UpdateCheckController $update_check_controller;
	private DownloadTokenController $download_token_controller;

	public function __construct(
		LicenseService $license_service,
		ActivationService $activation_service,
		ValidationService $validation_service,
		PackageDownloadService $download_service,
		VersionRepository $version_repo,
		ProductRepository $product_repo
	) {
		$this->activate_controller       = new ActivateController( $activation_service );
		$this->deactivate_controller     = new DeactivateController( $activation_service );
		$this->validate_controller       = new ValidateController( $validation_service );
		$this->update_check_controller   = new UpdateCheckController( $version_repo, $product_repo, $validation_service, $download_service );
		$this->download_token_controller = new DownloadTokenController( $download_service );
	}

	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		$this->activate_controller->register_routes( self::NAMESPACE );
		$this->deactivate_controller->register_routes( self::NAMESPACE );
		$this->validate_controller->register_routes( self::NAMESPACE );
		$this->update_check_controller->register_routes( self::NAMESPACE );
		$this->download_token_controller->register_routes( self::NAMESPACE );
	}
}
