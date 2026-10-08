# WPCalibrate License Manager

[![WordPress](https://img.shields.io/badge/WordPress-6.5%20--%207.1.3%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.2%20--%208.3%2B-purple.svg)](https://php.net)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-9.0%20--%2011.2.0%2B-green.svg)](https://woocommerce.com)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-orange.svg)](LICENSE)
[![Release](https://img.shields.io/github/v/release/zeeshanraza-official/wpcalibrate-license-manager?color=brightgreen)](https://github.com/zeeshanraza-official/wpcalibrate-license-manager/releases)

Centralized licensing authority, product versioning, secure private updates, and automated license distribution for WPCalibrate commercial plugins and themes.

---

## About

**WPCalibrate License Manager** is an enterprise-grade, high-performance licensing server and distribution engine designed for WordPress plugin businesses. Built from the ground up with strict security architecture, it provides everything required to issue cryptographically secure license keys, manage multi-tier seat limits, distribute private updates through core WordPress update screens, and automatically provision licenses upon WooCommerce checkout.

The platform pairs a central licensing server plugin with an ultra-lightweight, drop-in client SDK (`WPCalibrate\LicensingSDK`) that embeds directly inside commercial plugins without global namespace collisions.

```text
+---------------------------------------------------------------------------------+
|                       WPCalibrate Central Licensing Server                      |
|                                                                                 |
|  [ Products & Versions ]   [ License Generator ]   [ Native Update Transients ] |
|  - Custom SQL tables       - CSPRNG 128-bit keys   - Short-lived HMAC tokens    |
|  - SemVer release assets   - Masked key storage    - Core upgrader bridge       |
+---------------------------------------------------------------------------------+
                                       ▲
                                       │ REST API v1 (JSON over HTTPS)
                                       ▼
+---------------------------------------------------------------------------------+
|                     Commercial Plugin + Embedded Client SDK                     |
|                                                                                 |
|  - License Entry & Status Cards                    - 7-Day Outage Grace Period  |
|  - In-Dashboard Core Upgrades                      - Staging Domain Exemptions  |
+---------------------------------------------------------------------------------+
```

---

## Features

### Authoritative Licensing Server
* **Custom High-Performance DB Schema**: Uses dedicated indexed tables (`wpcalibrate_products`, `wpcalibrate_licenses`, `wpcalibrate_activations`, `wpcalibrate_versions`, `wpcalibrate_license_events`) with integer primary keys, foreign key constraints, and multi-column indexes.
* **CSPRNG License Keys**: Generates cryptographically secure 128-bit license keys formatted as `WPC-XXXX-XXXX-XXXX-XXXX` with masked storage (`WPC-****-****-****-XXXX`) and SHA-256 hashed lookup.
* **Granular Activation Limits**: Supports single-site, multi-site, and unlimited seat allocations with real-time seat enforcement and idempotent deactivations.
* **Staging & Local Site Exemption**: Automatically exempts local and staging environments (`localhost`, `*.local`, `*.test`, `staging.*`, `dev.*`, private IPs) without consuming customer seat quotas.
* **Comprehensive Audit Trail**: Records lifecycle events (`created`, `activated`, `deactivated`, `expired`, `revoked`, `download_token_issued`) with IP addresses, domains, and sensitive parameter masking.

### Private Update Delivery
* **Native WordPress Upgrades**: Serves authorized plugin updates directly through core WordPress update transients (`pre_set_site_transient_update_plugins`) and plugin detail dialogs (`plugins_api`).
* **HMAC-SHA256 Download Security**: Package downloads require short-lived (15-minute) single-use HMAC-SHA256 download tokens. Raw license keys are never leaked in update URLs.
* **Server-Side File Verification**: Releases are validated via SHA-256 file hashes before delivery to prevent tampered payloads.

### WooCommerce Integration
* **HPOS Compatible**: Fully compatible with WooCommerce High-Performance Order Storage (HPOS) and legacy post storage.
* **Automated Provisioning**: Automatically generates and activates licenses when WooCommerce orders reach `completed` status.
* **Idempotent Webhooks**: Guards against duplicate license creation during repeated webhooks or order transitions.
* **Refund Handling**: Automatically revokes or deactivates licenses when orders are refunded.

### Reusable Client SDK
* **Zero External Dependencies**: Pure PHP client SDK requiring zero third-party packages or Composer dependencies.
* **Multi-Plugin Isolation**: Uses isolated option keys (`wpcalibrate_lic_{slug}`) and transient caches so multiple WPCalibrate plugins can run on the same site without interference.
* **7-Day Outage Resilience**: In case of temporary licensing server downtime, cached valid licenses continue working for a 7-day grace period without disrupting end-user workflows.
* **GitHub Release Updater**: Native in-dashboard 1-click update support directly from GitHub releases.

---

## System Requirements

| Component | Minimum Version | Recommended Version |
| :--- | :--- | :--- |
| **WordPress** | 6.5+ | 6.7+ / 7.1+ |
| **PHP** | 8.2 | 8.2 or 8.3 |
| **MySQL / MariaDB** | MySQL 5.7+ / MariaDB 10.3+ | MySQL 8.0+ / MariaDB 10.6+ |
| **WooCommerce** *(Optional)* | 9.0+ | 10.0+ / 11.2+ (HPOS Mode) |
| **PHP Extensions** | `openssl`, `json`, `mbstring`, `curl` | Enabled by default |

---

## Documentation & Usage

### 1. Installation

#### WordPress Admin Upload
1. Download the latest packaged release: [`wpcalibrate-license-manager-1.1.2.zip`](https://github.com/zeeshanraza-official/wpcalibrate-license-manager/releases/latest).
2. Go to **WordPress Admin** &rarr; **Plugins** &rarr; **Add New Plugin** &rarr; **Upload Plugin**.
3. Select the downloaded ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

#### Git Clone
```bash
cd wp-content/plugins
git clone https://github.com/zeeshanraza-official/wpcalibrate-license-manager.git wpcalibrate-license-manager
```

---

### 2. Administrator Workflow

1. Navigate to **WPCalibrate** &rarr; **License Manager** in the WordPress sidebar.
2. **Add Products**: Go to the **Products** tab, enter your commercial product name, unique slug (e.g. `wpcalibrate-custom-plugin`), and default seat limits.
3. **Upload Releases**: Go to the **Releases** tab, assign a version number (e.g. `1.2.0`), changelog, and upload or point to the production ZIP package.
4. **Issue Licenses**: Generate keys manually or let WooCommerce issue them automatically upon checkout.
5. **Monitor Activations**: View real-time active domains, seat consumption, and audit trails under the **Activations** and **Audit Log** tabs.

---

### 3. Client SDK Integration

To enable licensing and automatic updates inside your commercial plugin, embed the client SDK:

```php
// Copy licensing/wpcalibrate-licensing-sdk/ into your commercial plugin folder:
require_once __DIR__ . '/licensing/wpcalibrate-licensing-sdk/bootstrap.php';

use WPCalibrate\LicensingSDK\Client;

add_action( 'plugins_loaded', function () {
    $sdk = new Client( array(
        'server_url'      => 'https://licensing.wpcalibrate.com',
        'product_slug'    => 'my-commercial-plugin',
        'current_version' => '1.0.0',
        'plugin_file'     => __FILE__,
        'menu_slug'       => 'my-plugin-settings',
    ) );
    
    $sdk->init();
} );
```

---

### 4. REST API Endpoints

All endpoints are served under `/wp-json/wpcalibrate/v1/`:

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/wpcalibrate/v1/activate` | `POST` | Activates a license key for a given site domain and environment. |
| `/wpcalibrate/v1/deactivate` | `POST` | Deactivates an active site, releasing the seat limit. |
| `/wpcalibrate/v1/validate` | `POST` | Validates current license status, seat counts, and expiration dates. |
| `/wpcalibrate/v1/update-check` | `POST` | Checks for newer version releases and issues HMAC download tokens. |
| `/wpcalibrate/v1/download` | `GET` | Delivers the secured release package using an HMAC-SHA256 download token. |

---

## In-Dashboard Automatic Updates

This plugin includes built-in GitHub Release Updater integration:
1. When a new release is published on GitHub, WordPress automatically detects the new version.
2. An **"Update Available"** notice appears on the **Plugins** screen (`wp-admin/plugins.php`).
3. Click **Update Now** to update the plugin directly in your WordPress admin dashboard.

---

## Changelog

### [1.1.2] - 2026-10-08
* **Fixed**: Resolved undefined constant fatal error during activation on PHP 8.2+ (`parent_exists` bareword fix in `Menu.php`).
* **Fixed**: Eliminated duplicate plugin listing in WordPress admin plugins screen by removing redundant legacy folders.
* **Added**: Native `GitHubUpdater` service for 1-click in-dashboard updates via GitHub releases.
* **Added**: Automated release packaging and distribution workflows.

### [1.1.1] - 2026-10-08
* **Fixed**: Hardened admin tab template inclusion with defensive `file_exists()` and `Throwable` exception handlers.
* **Fixed**: Synchronized default landing tab between `header.php` and `Menu.php` to `'overview'`.
* **Added**: Automatic database migration checks on `admin_init` and plugin boot.

### [1.1.0] - 2026-10-08
* **Added**: Comprehensive "Overview" tab in plugin settings detailing developer workflow, SDK code snippet, REST API table, and architecture.
* **Fixed**: Admin sidebar branding logo styling with unified 20x20px dimension constraints.

### [1.0.0] - 2026-10-08
* **Added**: Initial production release.
* **Added**: High-performance indexed custom database tables.
* **Added**: Reusable client licensing SDK (`WPCalibrate\LicensingSDK`).
* **Added**: Native WordPress update transients with HMAC-SHA256 secure tokens.
* **Added**: WooCommerce HPOS adapter for automated license creation upon purchase.
* **Added**: Automated test suite with 16 comprehensive unit and integration tests.

---

## License & Credits

* **Author**: [WPCalibrate](https://wpcalibrate.com)
* **Website**: [https://wpcalibrate.com](https://wpcalibrate.com)
* **Support**: [support@wpcalibrate.com](mailto:support@wpcalibrate.com)
* **License**: [GNU General Public License v2.0 or later](LICENSE)
* **Copyright**: &copy; 2026 WPCalibrate. All rights reserved.
