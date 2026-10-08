<?php
/**
 * WPCalibrate Licensing SDK Main Client.
 *
 * Isolated per-product instance coordinator.
 *
 * @package WPCalibrate\LicensingSDK
 */

declare(strict_types=1);

namespace WPCalibrate\LicensingSDK;

use WPCalibrate\LicensingSDK\Admin\LicenseSettingsSection;
use WPCalibrate\LicensingSDK\Api\Transport;
use WPCalibrate\LicensingSDK\Cache\LicenseCache;
use WPCalibrate\LicensingSDK\License\LicenseManager;
use WPCalibrate\LicensingSDK\Updates\PluginUpdater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Client
 */
final class Client {

	/**
	 * Registry of initialized client instances keyed by plugin slug.
	 *
	 * @var array<string, self>
	 */
	private static array $instances = array();

	private Config $config;
	private Transport $transport;
	private LicenseCache $cache;
	private LicenseManager $license_manager;
	private PluginUpdater $updater;
	private LicenseSettingsSection $settings_section;

	/**
	 * Initialize or retrieve a client instance for a commercial plugin.
	 *
	 * @param Config $config Product configuration object.
	 * @return self
	 */
	public static function init( Config $config ): self {
		$slug = $config->get_plugin_slug();
		if ( ! isset( self::$instances[ $slug ] ) ) {
			self::$instances[ $slug ] = new self( $config );
		}
		return self::$instances[ $slug ];
	}

	/**
	 * Retrieve an existing client instance by plugin slug.
	 *
	 * @param string $plugin_slug
	 * @return self|null
	 */
	public static function get_instance( string $plugin_slug ): ?self {
		return self::$instances[ $plugin_slug ] ?? null;
	}

	/**
	 * Constructor.
	 *
	 * @param Config $config
	 */
	private function __construct( Config $config ) {
		$this->config           = $config;
		$this->transport        = new Transport( $config );
		$this->cache            = new LicenseCache( $config );
		$this->license_manager  = new LicenseManager( $config, $this->transport, $this->cache );
		$this->updater          = new PluginUpdater( $config, $this->transport, $this->license_manager );
		$this->settings_section = new LicenseSettingsSection( $config, $this->license_manager );

		$this->boot();
	}

	/**
	 * Boot hooks.
	 */
	private function boot(): void {
		// Initialize native update hooks.
		$this->updater->init();

		// Initialize admin settings form handler if in WP-Admin.
		if ( is_admin() ) {
			$this->settings_section->init();
		}
	}

	public function get_config(): Config {
		return $this->config;
	}

	public function get_license_manager(): LicenseManager {
		return $this->license_manager;
	}

	public function get_updater(): PluginUpdater {
		return $this->updater;
	}

	public function get_settings_section(): LicenseSettingsSection {
		return $this->settings_section;
	}

	/**
	 * Check if product license is currently active.
	 *
	 * @return bool
	 */
	public function is_license_active(): bool {
		return $this->license_manager->is_active();
	}

	/**
	 * Render the license management UI block.
	 */
	public function render_license_section(): void {
		$this->settings_section->render();
	}
}
