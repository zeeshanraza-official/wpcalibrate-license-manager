<?php
/**
 * Main Plugin Container & Orchestrator.
 *
 * @package WPCalibrate\LicenseManager
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager;

use WPCalibrate\LicenseManager\Admin\Menu;
use WPCalibrate\LicenseManager\Api\RestServer;
use WPCalibrate\LicenseManager\Commerce\WooCommerceAdapter;
use WPCalibrate\LicenseManager\Database\Repositories\ActivationRepository;
use WPCalibrate\LicenseManager\Database\Repositories\EventRepository;
use WPCalibrate\LicenseManager\Database\Repositories\LicenseRepository;
use WPCalibrate\LicenseManager\Database\Repositories\ProductRepository;
use WPCalibrate\LicenseManager\Database\Repositories\VersionRepository;
use WPCalibrate\LicenseManager\Downloads\PackageDownloadService;
use WPCalibrate\LicenseManager\Licensing\ActivationService;
use WPCalibrate\LicenseManager\Licensing\LicenseService;
use WPCalibrate\LicenseManager\Licensing\ValidationService;
use WPCalibrate\LicenseManager\Logging\AuditLogger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Repository instances.
	 */
	private ProductRepository $product_repo;
	private LicenseRepository $license_repo;
	private ActivationRepository $activation_repo;
	private VersionRepository $version_repo;
	private EventRepository $event_repo;

	/**
	 * Service instances.
	 */
	private AuditLogger $logger;
	private LicenseService $license_service;
	private ActivationService $activation_service;
	private ValidationService $validation_service;
	private PackageDownloadService $download_service;
	private ?WooCommerceAdapter $woocommerce_adapter = null;

	/**
	 * Admin Menu coordinator.
	 */
	private Menu $admin_menu;

	/**
	 * REST Server.
	 */
	private RestServer $rest_server;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_repositories();
		$this->init_services();
	}

	/**
	 * Initialize database repositories.
	 */
	private function init_repositories(): void {
		$this->product_repo    = new ProductRepository();
		$this->license_repo    = new LicenseRepository();
		$this->activation_repo = new ActivationRepository();
		$this->version_repo    = new VersionRepository();
		$this->event_repo      = new EventRepository();
	}

	/**
	 * Initialize domain services.
	 */
	private function init_services(): void {
		$this->logger             = new AuditLogger( $this->event_repo );
		$this->license_service    = new LicenseService( $this->license_repo, $this->product_repo, $this->logger );
		$this->activation_service = new ActivationService( $this->activation_repo, $this->license_repo, $this->product_repo, $this->logger );
		$this->validation_service = new ValidationService( $this->license_repo, $this->activation_repo, $this->product_repo, $this->logger );
		$this->download_service   = new PackageDownloadService( $this->version_repo, $this->license_repo, $this->product_repo, $this->activation_repo, $this->logger );
	}

	/**
	 * Boot all plugin subsystems and hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Load text domain.
		load_plugin_textdomain(
			'wpcalibrate-license-manager',
			false,
			dirname( plugin_basename( WPCALIBRATE_LM_FILE ) ) . '/languages'
		);

		// Ensure database tables exist and are up to date.
		if ( \WPCalibrate\LicenseManager\Database\Migrator::needs_migration() ) {
			\WPCalibrate\LicenseManager\Database\Installer::activate();
		}

		// Admin interface.
		if ( is_admin() ) {
			$this->admin_menu = new Menu(
				$this->product_repo,
				$this->license_repo,
				$this->activation_repo,
				$this->version_repo,
				$this->event_repo,
				$this->license_service,
				$this->activation_service,
				$this->download_service,
				$this->logger
			);
			$this->admin_menu->init();

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

			// GitHub In-Dashboard Updates.
			$github_updater = new \WPCalibrate\LicenseManager\Infrastructure\GitHubUpdater(
				WPCALIBRATE_LM_FILE,
				WPCALIBRATE_LM_VERSION
			);
			$github_updater->register();
		}

		// REST API.
		$this->rest_server = new RestServer(
			$this->license_service,
			$this->activation_service,
			$this->validation_service,
			$this->download_service,
			$this->version_repo,
			$this->product_repo
		);
		$this->rest_server->init();

		// Download handler via query variable or controller.
		$this->download_service->init();

		// Commerce adapter (WooCommerce when active).
		$this->init_commerce_adapter();

		// Background maintenance tasks.
		add_action( 'wpcalibrate_lm_daily_maintenance', array( $this, 'run_daily_maintenance' ) );
	}

	/**
	 * Initialize commerce adapter if WooCommerce is present.
	 */
	private function init_commerce_adapter(): void {
		if ( class_exists( 'WooCommerce' ) ) {
			$this->woocommerce_adapter = new WooCommerceAdapter(
				$this->license_service,
				$this->product_repo,
				$this->logger
			);
			$this->woocommerce_adapter->init();
		}
	}

	/**
	 * Enqueue assets only on WPCalibrate License Manager screens.
	 *
	 * @param string $hook Admin screen hook suffix.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'wpcalibrate-license-manager' ) ) {
			return;
		}

		wp_enqueue_style(
			'wpcalibrate-lm-admin-css',
			WPCALIBRATE_LM_URL . 'assets/css/admin.css',
			array(),
			WPCALIBRATE_LM_VERSION
		);

		wp_enqueue_script(
			'wpcalibrate-lm-admin-js',
			WPCALIBRATE_LM_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WPCALIBRATE_LM_VERSION,
			true
		);

		wp_localize_script(
			'wpcalibrate-lm-admin-js',
			'wpcalibrateLMData',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wpcalibrate_lm_admin_nonce' ),
				'confirmMsg' => __( 'Are you sure you want to proceed with this action?', 'wpcalibrate-license-manager' ),
			)
		);
	}

	/**
	 * Execute daily maintenance (cron).
	 * Prunes expired tokens, cleans old audit logs per retention setting, checks expired licenses.
	 */
	public function run_daily_maintenance(): void {
		$this->license_service->process_expired_licenses();
		$this->logger->prune_old_events();
		$this->download_service->cleanup_expired_tokens();
	}

	// Service Accessors.
	public function get_product_repo(): ProductRepository { return $this->product_repo; }
	public function get_license_repo(): LicenseRepository { return $this->license_repo; }
	public function get_activation_repo(): ActivationRepository { return $this->activation_repo; }
	public function get_version_repo(): VersionRepository { return $this->version_repo; }
	public function get_event_repo(): EventRepository { return $this->event_repo; }
	public function get_license_service(): LicenseService { return $this->license_service; }
	public function get_activation_service(): ActivationService { return $this->activation_service; }
	public function get_validation_service(): ValidationService { return $this->validation_service; }
	public function get_download_service(): PackageDownloadService { return $this->download_service; }
	public function get_logger(): AuditLogger { return $this->logger; }
}
