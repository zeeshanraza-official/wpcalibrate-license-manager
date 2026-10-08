<?php
/**
 * SDK Admin License Settings Section Component.
 *
 * Renders an isolated, secure license management card inside the host plugin's settings.
 *
 * @package WPCalibrate\LicensingSDK\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\LicensingSDK\Admin;

use WPCalibrate\LicensingSDK\Config;
use WPCalibrate\LicensingSDK\License\LicenseManager;
use WPCalibrate\LicensingSDK\License\Status;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LicenseSettingsSection
 */
final class LicenseSettingsSection {

	private Config $config;
	private LicenseManager $license_manager;

	public function __construct( Config $config, LicenseManager $license_manager ) {
		$this->config          = $config;
		$this->license_manager = $license_manager;
	}

	public function init(): void {
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
	}

	public function handle_form_submission(): void {
		if ( empty( $_POST['wpcalibrate_sdk_action'] ) ) {
			return;
		}

		$expected_nonce_action = 'wpcalibrate_sdk_lic_' . $this->config->get_plugin_slug();
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), $expected_nonce_action ) ) {
			return;
		}

		if ( ! current_user_can( $this->config->get_required_capability() ) ) {
			return;
		}

		$action = sanitize_key( $_POST['wpcalibrate_sdk_action'] );

		if ( 'activate' === $action ) {
			$raw_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) );
			$result  = $this->license_manager->activate( $raw_key );

			if ( $result['success'] ) {
				add_settings_error( $this->config->get_plugin_slug(), 'lic_success', $result['message'], 'success' );
			} else {
				add_settings_error( $this->config->get_plugin_slug(), 'lic_error', $result['message'], 'error' );
			}
		} elseif ( 'deactivate' === $action ) {
			$result = $this->license_manager->deactivate();
			add_settings_error( $this->config->get_plugin_slug(), 'lic_deactivated', $result['message'], 'success' );
		} elseif ( 'refresh' === $action ) {
			$result = $this->license_manager->validate( true );
			add_settings_error( $this->config->get_plugin_slug(), 'lic_refreshed', __( 'License status refreshed.', 'wpcalibrate-license-manager' ), 'info' );
		}
	}

	/**
	 * Render the license management section HTML.
	 */
	public function render(): void {
		$data      = $this->license_manager->get_license_data();
		$is_active = $this->license_manager->is_active();
		$status    = $data['status'] ?? Status::UNLICENSED;
		$nonce_act = 'wpcalibrate_sdk_lic_' . $this->config->get_plugin_slug();
		?>
		<div class="wpcalibrate-sdk-license-card" style="background:#fff;border:1px solid #ccd0d4;padding:20px;border-radius:4px;margin-bottom:20px;max-width:800px;">
			<h3 style="margin-top:0;display:flex;align-items:center;justify-content:space-between;">
				<span><?php echo esc_html( $this->config->get_plugin_name() ); ?> &mdash; <?php esc_html_e( 'License Management', 'wpcalibrate-license-manager' ); ?></span>
				<span style="font-size:12px;font-weight:600;padding:3px 10px;border-radius:3px;<?php echo $is_active ? 'background:#d1e7dd;color:#0f5132;' : 'background:#f8d7da;color:#842029;'; ?>">
					<?php echo esc_html( strtoupper( $status ) ); ?>
				</span>
			</h3>

			<?php settings_errors( $this->config->get_plugin_slug() ); ?>

			<form method="post">
				<?php wp_nonce_field( $nonce_act, '_wpnonce' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $this->config->get_plugin_slug() ); ?>_key"><?php esc_html_e( 'License Key', 'wpcalibrate-license-manager' ); ?></label></th>
						<td>
							<?php if ( ! empty( $data['license_key'] ) ) : ?>
								<input type="text" id="<?php echo esc_attr( $this->config->get_plugin_slug() ); ?>_key" value="<?php echo esc_attr( $data['license_key_masked'] ?? '********' ); ?>" class="regular-text" readonly style="background:#f6f7f7;" />
								<p class="description"><?php esc_html_e( 'Your license key is saved securely.', 'wpcalibrate-license-manager' ); ?></p>
							<?php else : ?>
								<input type="text" id="<?php echo esc_attr( $this->config->get_plugin_slug() ); ?>_key" name="license_key" class="regular-text code" placeholder="WPC-XXXX-XXXX-XXXX-XXXX" required />
								<p class="description"><?php esc_html_e( 'Enter the license key received after purchase.', 'wpcalibrate-license-manager' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>

					<?php if ( ! empty( $data['license_key'] ) ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Subscription / Expiry', 'wpcalibrate-license-manager' ); ?></th>
							<td>
								<?php if ( ! empty( $data['is_lifetime'] ) ) : ?>
									<strong><?php esc_html_e( 'Lifetime Access', 'wpcalibrate-license-manager' ); ?></strong>
								<?php elseif ( ! empty( $data['expires_at'] ) ) : ?>
									<strong><?php echo esc_html( substr( (string) $data['expires_at'], 0, 10 ) ); ?></strong>
									<?php if ( strtotime( $data['expires_at'] ) < time() ) : ?>
										<span style="color:#d63638;margin-left:8px;">(<?php esc_html_e( 'Expired', 'wpcalibrate-license-manager' ); ?>)</span>
										<a href="<?php echo esc_url( $this->config->get_renewal_url() ); ?>" target="_blank" rel="noopener noreferrer" class="button button-small" style="margin-left:8px;"><?php esc_html_e( 'Renew License', 'wpcalibrate-license-manager' ); ?></a>
									<?php endif; ?>
								<?php endif; ?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Seats Used', 'wpcalibrate-license-manager' ); ?></th>
							<td>
								<?php printf( esc_html__( '%1$d / %2$s sites activated', 'wpcalibrate-license-manager' ), (int) ( $data['activation_count'] ?? 1 ), 0 === (int) ( $data['activation_limit'] ?? 1 ) ? esc_html__( 'Unlimited', 'wpcalibrate-license-manager' ) : (string) $data['activation_limit'] ); ?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Last Checked', 'wpcalibrate-license-manager' ); ?></th>
							<td>
								<?php echo ! empty( $data['last_checked'] ) ? esc_html( gmdate( 'Y-m-d H:i:s', (int) $data['last_checked'] ) . ' UTC' ) : '-'; ?>
								<button type="submit" name="wpcalibrate_sdk_action" value="refresh" class="button button-small" style="margin-left:8px;"><?php esc_html_e( 'Check Now', 'wpcalibrate-license-manager' ); ?></button>
							</td>
						</tr>
					<?php endif; ?>
				</table>

				<p class="submit" style="margin-top:15px;padding-top:15px;border-top:1px solid #f0f0f1;">
					<?php if ( empty( $data['license_key'] ) ) : ?>
						<button type="submit" name="wpcalibrate_sdk_action" value="activate" class="button button-primary"><?php esc_html_e( 'Activate License', 'wpcalibrate-license-manager' ); ?></button>
					<?php else : ?>
						<button type="submit" name="wpcalibrate_sdk_action" value="deactivate" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Deactivate license from this site?', 'wpcalibrate-license-manager' ); ?>');"><?php esc_html_e( 'Deactivate License', 'wpcalibrate-license-manager' ); ?></button>
					<?php endif; ?>
				</p>
			</form>
		</div>
		<?php
	}
}
