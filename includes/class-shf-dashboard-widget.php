<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SHF_Dashboard_Widget {
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_dashboard_setup', [ $this, 'register_widget' ] );
		add_action( 'admin_post_shf_dashboard_save', [ $this, 'handle_save' ] );
	}

	public function register_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'shf_dashboard_widget',
			__( 'Security Headers Fixer', 'security-headers-fixer' ),
			[ $this, 'render_widget' ]
		);
	}

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'security-headers-fixer' ) );
		}

		check_admin_referer( 'shf_dashboard_save', 'shf_dashboard_nonce' );

		$post = wp_unslash( $_POST );
		SHF_Settings::update( $post );

		wp_safe_redirect( admin_url( 'index.php?shf_saved=1' ) );
		exit;
	}

	public function render_widget() {
		if ( isset( $_GET['shf_saved'] ) ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Saved.', 'security-headers-fixer' ) . '</p></div>';
		}

		$settings = SHF_Settings::get();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'shf_dashboard_save', 'shf_dashboard_nonce' ); ?>
			<input type="hidden" name="action" value="shf_dashboard_save" />

			<p>
				<label>
					<input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
					<strong><?php echo esc_html__( 'Enable fixes', 'security-headers-fixer' ); ?></strong>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="enable_csp" value="1" <?php checked( ! empty( $settings['enable_csp'] ) ); ?> />
					<?php echo esc_html__( 'Enable CSP (Report-Only)', 'security-headers-fixer' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="enable_csp_enforce_compat" value="1" <?php checked( ! empty( $settings['enable_csp_enforce_compat'] ) ); ?> />
					<?php echo esc_html__( 'Enable CSP header (compat mode)', 'security-headers-fixer' ); ?>
				</label>
				<br />
				<span class="description">
					<?php echo esc_html__( 'Adds an enforced CSP that is very permissive (scanner-friendly). If anything breaks, turn this off first.', 'security-headers-fixer' ); ?>
				</span>
			</p>

			<p>
				<label>
					<input type="checkbox" name="enable_upgrade_https" value="1" <?php checked( ! empty( $settings['enable_upgrade_https'] ) ); ?> />
					<?php echo esc_html__( 'Upgrade HTTP → HTTPS (CSP)', 'security-headers-fixer' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="enable_hsts" value="1" <?php checked( ! empty( $settings['enable_hsts'] ) ); ?> />
					<?php echo esc_html__( 'Enable HSTS (HTTPS only)', 'security-headers-fixer' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="harden_target_blank" value="1" <?php checked( ! empty( $settings['harden_target_blank'] ) ); ?> />
					<?php echo esc_html__( 'Fix unsafe cross-origin links (target=_blank)', 'security-headers-fixer' ); ?>
				</label>
			</p>

			<p>
				<strong><?php echo esc_html__( 'Dashboard menu position', 'security-headers-fixer' ); ?></strong><br />
				<label>
					<input type="radio" name="dashboard_menu_position" value="top" <?php checked( ( $settings['dashboard_menu_position'] ?? 'top' ), 'top' ); ?> />
					<?php echo esc_html__( 'Top', 'security-headers-fixer' ); ?>
				</label>
				&nbsp;&nbsp;
				<label>
					<input type="radio" name="dashboard_menu_position" value="bottom" <?php checked( ( $settings['dashboard_menu_position'] ?? 'top' ), 'bottom' ); ?> />
					<?php echo esc_html__( 'Bottom', 'security-headers-fixer' ); ?>
				</label>
			</p>

			<p>
				<?php submit_button( __( 'Save', 'security-headers-fixer' ), 'primary', 'submit', false ); ?>
				<span style="margin-left: 8px;">
					<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">
						<?php echo esc_html__( 'Plugins', 'security-headers-fixer' ); ?>
					</a>
				</span>
			</p>
		</form>
		<?php
	}
}

