<?php
/**
 * Admin Menu Coordinator for WPCalibrate License Manager.
 *
 * @package WPCalibrate\LicenseManager\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\LicenseManager\Admin;

use WPCalibrate\LicenseManager\Database\Repositories\ActivationRepository;
use WPCalibrate\LicenseManager\Database\Repositories\EventRepository;
use WPCalibrate\LicenseManager\Database\Repositories\LicenseRepository;
use WPCalibrate\LicenseManager\Database\Repositories\ProductRepository;
use WPCalibrate\LicenseManager\Database\Repositories\VersionRepository;
use WPCalibrate\LicenseManager\Downloads\PackageDownloadService;
use WPCalibrate\LicenseManager\Licensing\ActivationService;
use WPCalibrate\LicenseManager\Licensing\LicenseService;
use WPCalibrate\LicenseManager\Logging\AuditLogger;
use WPCalibrate\LicenseManager\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Menu
 */
final class Menu {

	public const PARENT_MENU_SLUG = 'wpcalibrate';
	public const SUBMENU_SLUG     = 'wpcalibrate-license-manager';

	public ProductRepository $product_repo;
	public LicenseRepository $license_repo;
	public ActivationRepository $activation_repo;
	public VersionRepository $version_repo;
	public EventRepository $event_repo;
	public LicenseService $license_service;
	public ActivationService $activation_service;
	public PackageDownloadService $download_service;
	public AuditLogger $logger;

	public function __construct(
		ProductRepository $product_repo,
		LicenseRepository $license_repo,
		ActivationRepository $activation_repo,
		VersionRepository $version_repo,
		EventRepository $event_repo,
		LicenseService $license_service,
		ActivationService $activation_service,
		PackageDownloadService $download_service,
		AuditLogger $logger
	) {
		$this->product_repo       = $product_repo;
		$this->license_repo       = $license_repo;
		$this->activation_repo    = $activation_repo;
		$this->version_repo       = $version_repo;
		$this->event_repo         = $event_repo;
		$this->license_service    = $license_service;
		$this->activation_service = $activation_service;
		$this->download_service   = $download_service;
		$this->logger             = $logger;
	}

	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menus' ), 9 );
		add_action( 'admin_init', array( $this, 'maybe_run_migrations' ), 5 );
		add_action( 'admin_init', array( $this, 'handle_form_actions' ) );
		add_action( 'admin_head', array( $this, 'render_menu_icon_styles' ) );
	}

	/**
	 * Run database migrations on admin_init if database schema is out of date or tables were not created.
	 *
	 * Guarantees custom database tables exist even when plugin files are deployed via FTP without reactivation.
	 */
	public function maybe_run_migrations(): void {
		if ( ! Capabilities::current_user_can_manage() ) {
			return;
		}

		if ( \WPCalibrate\LicenseManager\Database\Migrator::needs_migration() ) {
			\WPCalibrate\LicenseManager\Database\Installer::activate();
		}
	}

	/**
	 * Output global CSS to ensure side menu branding icon is perfectly sized on all admin pages.
	 */
	public function render_menu_icon_styles(): void {
		?>
		<style>
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding: 7px 0 0 0 !important;
				box-sizing: content-box;
				object-fit: contain;
				opacity: 0.85;
				transition: opacity 0.15s ease-in-out;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu .wp-menu-image img {
				opacity: 1 !important;
			}
		</style>
		<?php
	}

	/**
	 * Register the shared WPCalibrate top-level menu and this plugin's submenu.
	 */
	public function register_menus(): void {
		global $menu;

		$capability = Capabilities::required_capability();

		// Check if top-level WPCalibrate parent menu already exists.
		$parent_exists = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && self::PARENT_MENU_SLUG === $item[2] ) {
					$parent_exists = true;
					break;
				}
			}
		}

		$icon_url = $this->get_admin_icon();

		if ( ! $parent_exists ) {
			add_menu_page(
				'WPCalibrate',
				'WPCalibrate',
				$capability,
				self::PARENT_MENU_SLUG,
				array( $this, 'render_main_page' ),
				$icon_url,
				59
			);
		}

		// Register License Manager submenu.
		add_submenu_page(
			self::PARENT_MENU_SLUG,
			__( 'License Manager', 'wpcalibrate-license-manager' ),
			__( 'License Manager', 'wpcalibrate-license-manager' ),
			$capability,
			self::SUBMENU_SLUG,
			array( $this, 'render_main_page' )
		);
	}

	/**
	 * Retrieve direct URL for the white icon on dark admin menu.
	 *
	 * Note: Must return a valid HTTP/HTTPS URL, NOT a data URI, because
	 * WordPress core wraps menu icons in esc_url(), which strips data: URIs.
	 *
	 * @return string
	 */
	private function get_admin_icon(): string {
		$white_icon_path = WPCALIBRATE_LM_DIR . 'branding/icon-white.png';
		if ( file_exists( $white_icon_path ) ) {
			return WPCALIBRATE_LM_URL . 'branding/icon-white.png';
		}

		return 'dashicons-admin-network';
	}

	/**
	 * Handle admin POST/GET submissions with nonces and capability checks.
	 */
	public function handle_form_actions(): void {
		if ( ! isset( $_GET['page'] ) || self::SUBMENU_SLUG !== $_GET['page'] ) {
			return;
		}

		if ( ! Capabilities::current_user_can_manage() ) {
			return;
		}

		// Process tab-specific mutations.
		( new ActionHandler(
			$this->product_repo,
			$this->license_repo,
			$this->activation_repo,
			$this->version_repo,
			$this->license_service,
			$this->activation_service,
			$this->logger
		) )->handle();
	}

	/**
	 * Render the unified tabbed License Manager interface.
	 */
	public function render_main_page(): void {
		if ( ! Capabilities::current_user_can_manage() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpcalibrate-license-manager' ) );
		}

		$current_tab = sanitize_key( $_GET['tab'] ?? 'overview' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$valid_tabs = array(
			'overview',
			'dashboard',
			'products',
			'licenses',
			'activations',
			'releases',
			'settings',
			'diagnostics',
			'contact',
		);

		if ( ! in_array( $current_tab, $valid_tabs, true ) ) {
			$current_tab = 'overview';
		}

		$header_file = WPCALIBRATE_LM_DIR . 'includes/Admin/Views/header.php';
		if ( file_exists( $header_file ) ) {
			require $header_file;
		}

		$tab_file = WPCALIBRATE_LM_DIR . 'includes/Admin/Views/tab-' . $current_tab . '.php';

		if ( file_exists( $tab_file ) ) {
			try {
				require $tab_file;
			} catch ( \Throwable $e ) {
				echo '<div class="notice notice-error inline"><p>' .
					esc_html( sprintf( __( 'Error rendering tab [%1$s]: %2$s', 'wpcalibrate-license-manager' ), $current_tab, $e->getMessage() ) ) .
					'</p></div>';
			}
		} else {
			$fallback_file = WPCALIBRATE_LM_DIR . 'includes/Admin/Views/tab-overview.php';
			if ( file_exists( $fallback_file ) ) {
				echo '<div class="notice notice-warning inline"><p>' .
					esc_html( sprintf( __( 'Tab template [tab-%s.php] was not found on this server. Displaying Overview instead.', 'wpcalibrate-license-manager' ), $current_tab ) ) .
					'</p></div>';
				require $fallback_file;
			} else {
				echo '<div class="notice notice-error inline"><p>' .
					esc_html__( 'Admin view templates are missing from the server. Please redeploy plugin files.', 'wpcalibrate-license-manager' ) .
					'</p></div>';
			}
		}

		$footer_file = WPCALIBRATE_LM_DIR . 'includes/Admin/Views/footer.php';
		if ( file_exists( $footer_file ) ) {
			require $footer_file;
		}
	}
}
