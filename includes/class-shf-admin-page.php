<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SHF_Admin_Page {
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_save' ] );
	}

	public function register_menu() {
		$settings = SHF_Settings::get();
		$position = ( ! empty( $settings['dashboard_menu_position'] ) && $settings['dashboard_menu_position'] === 'bottom' ) ? 99 : 1;

		add_submenu_page(
			'index.php',
			__( 'Security Headers Fixer', 'security-headers-fixer' ),
			__( 'Security Headers', 'security-headers-fixer' ),
			'manage_options',
			'shf-settings',
			[ $this, 'render_page' ],
			$position
		);

		add_management_page(
			__( 'Security Headers Fixer', 'security-headers-fixer' ),
			__( 'Security Headers', 'security-headers-fixer' ),
			'manage_options',
			'shf-settings',
			[ $this, 'render_page' ]
		);
	}

	public function handle_save() {
		if ( ! isset( $_POST['shf_settings_nonce'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shf_settings_nonce'] ) ), 'shf_save_settings' ) ) {
			return;
		}

		$post = wp_unslash( $_POST );
		SHF_Settings::update( $post );

		add_settings_error( 'shf_settings', 'shf_saved', __( 'Settings saved.', 'security-headers-fixer' ), 'updated' );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = SHF_Settings::get();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Security Headers Fixer', 'security-headers-fixer' ); ?></h1>
			<?php settings_errors( 'shf_settings' ); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'shf_save_settings', 'shf_settings_nonce' ); ?>

				<h2 class="title"><?php echo esc_html__( 'Simple settings', 'security-headers-fixer' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Enable fixes', 'security-headers-fixer' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
								<?php echo esc_html__( 'Yes — add the missing security headers', 'security-headers-fixer' ); ?>
							</label>
							<p class="description">
								<?php echo esc_html__( 'Adds Referrer-Policy, X-Content-Type-Options, X-Frame-Options, and (optionally) CSP/HSTS.', 'security-headers-fixer' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Enable CSP (Report-Only)', 'security-headers-fixer' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enable_csp" value="1" <?php checked( ! empty( $settings['enable_csp'] ) ); ?> />
								<?php echo esc_html__( 'Yes — send Content-Security-Policy-Report-Only', 'security-headers-fixer' ); ?>
							</label>
							<p class="description"><?php echo esc_html__( 'Report-Only won’t block resources; it satisfies “Missing Content-Security-Policy header”.', 'security-headers-fixer' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Enable CSP (Header)', 'security-headers-fixer' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enable_csp_enforce_compat" value="1" <?php checked( ! empty( $settings['enable_csp_enforce_compat'] ) ); ?> />
								<?php echo esc_html__( 'Yes — send Content-Security-Policy (compat mode)', 'security-headers-fixer' ); ?>
							</label>
							<p class="description">
								<?php echo esc_html__( 'This adds an enforced CSP header that is very permissive (scanner-friendly). If anything breaks, turn this off first.', 'security-headers-fixer' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Upgrade HTTP → HTTPS (CSP)', 'security-headers-fixer' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enable_upgrade_https" value="1" <?php checked( ! empty( $settings['enable_upgrade_https'] ) ); ?> />
								<?php echo esc_html__( 'Yes — add upgrade-insecure-requests', 'security-headers-fixer' ); ?>
							</label>
							<p class="description"><?php echo esc_html__( 'Helps reduce mixed-content by upgrading http:// subresources when possible.', 'security-headers-fixer' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html__( 'HSTS (HTTPS only)', 'security-headers-fixer' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Enable HSTS', 'security-headers-fixer' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enable_hsts" value="1" <?php checked( ! empty( $settings['enable_hsts'] ) ); ?> />
								<?php echo esc_html__( 'Yes — send Strict-Transport-Security', 'security-headers-fixer' ); ?>
							</label>
							<p class="description"><?php echo esc_html__( 'Turn this on only if your whole site is fully HTTPS.', 'security-headers-fixer' ); ?></p>
						</td>
					</tr>
				</table>

				<hr />

				<h2 class="title"><?php echo esc_html__( 'Link hardening', 'security-headers-fixer' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'target=_blank', 'security-headers-fixer' ); ?></th>
						<td><label><input type="checkbox" name="harden_target_blank" value="1" <?php checked( ! empty( $settings['harden_target_blank'] ) ); ?> /> <?php echo esc_html__( 'Yes — fix “unsafe cross-origin links”', 'security-headers-fixer' ); ?></label></td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'security-headers-fixer' ) ); ?>
			</form>
		</div>
		<?php
	}
}

