=== WPCalibrate License Manager ===
Contributors: wpcalibrate
Donate link: https://wpcalibrate.com
Tags: license manager, software licensing, private updates, woocommerce licensing, plugin updates
Requires at least: 6.5
Tested up to: 7.1.3
Requires PHP: 8.2
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Centralized licensing authority, product versioning, secure private updates, and automated license distribution for WPCalibrate plugins.

== Description ==

WPCalibrate License Manager provides a complete, robust licensing server and distribution engine for commercial WordPress plugins and themes. Built from the ground up for high-performance and security, it allows authors to issue cryptographically secure license keys, manage seat limits, distribute private updates through core WordPress update screens, and integrate seamlessly with WooCommerce.

= Key Capabilities =

* **Authoritative Licensing Server:** Manage products, releases, license keys, customer associations, and site activations.
* **CSPRNG License Keys:** Generates cryptographically secure 128-bit keys formatted as `WPC-XXXX-XXXX-XXXX-XXXX` with masked storage and SHA-256 hashed lookup.
* **Granular Activation Limits:** Configure seat counts (single-site, multi-site, or unlimited) with automated seat tracking and idempotent site activations.
* **Native WordPress Private Updates:** Serves authorized updates via native WordPress update transients (`pre_set_site_transient_update_plugins` and `plugins_api`) using short-lived HMAC-signed download tokens.
* **WooCommerce Adapter:** Optional, fully HPOS-compatible integration that automatically provisions licenses on completed orders and manages refunds.
* **Reusable Client SDK:** Lightweight embedded PHP library (`WPCalibrate\LicensingSDK`) included in commercial plugins without global collisions.
* **Staging & Development Site Exemption:** Configurable wildcard domain matching for local and staging environments that do not consume production seats.
* **Server Outage Resilience:** Client SDK 7-day grace period ensures temporary server outages never break customer installations.
* **Unified WPCalibrate Admin Menu:** Reusable parent menu with collision prevention and isolated submenu settings.
* **In-Dashboard GitHub Updates:** Seamless 1-click update delivery through core WordPress update screens via GitHub Releases.

== Installation ==

1. Upload the `wpcalibrate-license-manager` directory to your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access **WPCalibrate -> License Manager** in the WordPress dashboard.
4. Add your commercial products and releases under the respective tabs.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
No. WooCommerce is entirely optional. When WooCommerce is not present, WPCalibrate License Manager operates as a standalone licensing server with complete administrative generation and REST API validation.

= Are license keys stored in plaintext? =
No. Keys are looked up via SHA-256 cryptographic hashes and encrypted using AES-256-CBC with a server secret key. Keys are always masked in admin screens and excluded from debug logs.

= How do updates work for client sites? =
Commercial plugins embed the WPCalibrate Licensing SDK. The SDK queries the licensing server's `/update-check` REST endpoint. When an update is available and the license is active, a short-lived download token is issued, enabling the native WordPress plugin upgrader to download and install the package.

== Changelog ==

= 1.1.2 =
* Fix: Resolved undefined constant error during activation on PHP 8.2+.
* Fix: Prevented duplicate plugin listing in WordPress admin plugins screen.
* Add: Built-in GitHub release updater for 1-click updates in WordPress dashboard.

= 1.1.1 =
* Fix: Hardened tab rendering with defensive template verification.
* Add: Automatic database migration check on plugin initialization.

= 1.1.0 =
* Add: Comprehensive Overview tab with usage guides and SDK code snippets.
* Fix: Admin sidebar menu branding icon styling.

= 1.0.0 =
* Initial release.
* Added authoritative licensing engine, REST API v1, WooCommerce adapter, and reusable client SDK.

== Upgrade Notice ==

= 1.1.2 =
* Upgrade recommended for full PHP 8.2+ compatibility and GitHub Release Updater integration.
