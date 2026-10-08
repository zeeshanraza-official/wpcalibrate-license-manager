# WPCalibrate Licensing SDK

A lightweight, production-ready, reusable PHP licensing client for commercial WordPress plugins developed by WPCalibrate.

---

## Key Features

- **Zero Global Collisions**: Completely namespaced (`WPCalibrate\LicensingSDK`), strict typing, and isolated state storage per product.
- **Multiple Products Support**: Run multiple WPCalibrate plugins concurrently on the same WordPress installation without conflicts.
- **Native WordPress Updates**: Hooks into core `pre_set_site_transient_update_plugins` and `plugins_api` for in-dashboard 1-click updates.
- **Security-First**: Short-lived authorized download tokens, masked license key display, and strict capability checks (`manage_options`).
- **Resilient Outage Handling**: Automatic 7-day grace period caching so temporary licensing server outages never break client installations.
- **Drop-in UI Component**: Self-contained license settings section ready to embed into any admin tab.

---

## Integration Guide

### 1. Include the SDK inside your plugin directory

Place the SDK inside your plugin, typically at:
```
your-plugin/
  licensing/wpcalibrate-licensing-sdk/
    bootstrap.php
    src/
```

### 2. Initialize in your main plugin file

```php
<?php
// your-plugin.php

// 1. Require SDK bootstrap loader
require_once __DIR__ . '/licensing/wpcalibrate-licensing-sdk/bootstrap.php';

use WPCalibrate\LicensingSDK\Client;
use WPCalibrate\LicensingSDK\Config;

// 2. Initialize with configuration
$config = new Config([
    'product_id'          => 1, // Authoritative product ID from marketplace.wpcalibrate.com
    'plugin_slug'         => 'wpcalibrate-pro',
    'plugin_name'         => 'WPCalibrate Pro',
    'current_version'     => '1.0.0',
    'main_plugin_file'    => __FILE__,
    'license_server_url'  => 'https://marketplace.wpcalibrate.com',
    'settings_page_slug'  => 'wpcalibrate-pro-settings',
    'required_capability' => 'manage_options',
    'text_domain'         => 'wpcalibrate-pro',
    'renewal_url'         => 'https://marketplace.wpcalibrate.com/checkout?renew=1',
]);

$licensing = Client::init( $config );

// 3. Render license section inside your plugin settings screen
function wpcalibrate_pro_render_license_tab() {
    $licensing = Client::get_instance( 'wpcalibrate-pro' );
    if ( $licensing ) {
        $licensing->render_license_section();
    }
}

// 4. (Optional) Check license state for premium feature gating
if ( $licensing->is_license_active() ) {
    // Enable premium feature
}
```

---

## Configuration Reference

| Option | Type | Required | Description |
|---|---|---|---|
| `product_id` | `int` | Yes | Numeric product ID on the WPCalibrate licensing server. |
| `plugin_slug` | `string` | Yes | WordPress directory slug of the plugin (e.g. `wpcalibrate-seo`). |
| `plugin_name` | `string` | Yes | Human-readable name of the plugin. |
| `current_version` | `string` | Yes | Current installed version string (e.g. `1.2.0`). |
| `main_plugin_file` | `string` | Yes | Path to the main plugin PHP file (`__FILE__`). |
| `license_server_url` | `string` | Yes | Base URL of licensing server (e.g. `https://marketplace.wpcalibrate.com`). |
| `settings_page_slug` | `string` | No | Submenu slug where license settings are rendered. |
| `required_capability`| `string` | No | WordPress capability to manage licenses (Default: `manage_options`). |
| `text_domain` | `string` | No | Host plugin text domain. |
| `renewal_url` | `string` | No | Web URL where customers can renew expired licenses. |

---

## License

GPL-2.0-or-later. &copy; WPCalibrate.
