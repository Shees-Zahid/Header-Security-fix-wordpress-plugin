<?php
/**
 * Plugin Name: Security Headers Fixer
 * Description: Adds security headers (HSTS, CSP, Referrer-Policy, X-Frame-Options) and hardens target=_blank links.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Shees
 * License: GPL-2.0-or-later
 * Text Domain: security-headers-fixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SHF_VERSION', '1.0.0' );
define( 'SHF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SHF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SHF_OPTION_KEY', 'shf_settings' );

require_once SHF_PLUGIN_DIR . 'includes/class-shf-settings.php';
require_once SHF_PLUGIN_DIR . 'includes/class-shf-admin-page.php';
require_once SHF_PLUGIN_DIR . 'includes/class-shf-headers.php';
require_once SHF_PLUGIN_DIR . 'includes/class-shf-link-hardener.php';
require_once SHF_PLUGIN_DIR . 'includes/class-shf-dashboard-widget.php';

final class SHF_Plugin {
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	public function init() {
		SHF_Headers::instance();
		SHF_Link_Hardener::instance();
		SHF_Dashboard_Widget::instance();

		if ( is_admin() ) {
			SHF_Admin_Page::instance();
		}
	}
}

SHF_Plugin::instance();

