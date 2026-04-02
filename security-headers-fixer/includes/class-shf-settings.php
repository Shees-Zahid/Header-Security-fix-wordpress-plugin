<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SHF_Settings {
	public static function recommended_csp() {
		// Intentionally permissive to avoid breaking page builders; goal is to satisfy "missing CSP header".
		return "default-src 'self';\nimg-src 'self' data: https:;\nscript-src 'self' 'unsafe-inline' 'unsafe-eval' https:;\nstyle-src 'self' 'unsafe-inline' https:;\nfont-src 'self' data: https:;\nconnect-src 'self' https:;\nframe-ancestors 'self';\nbase-uri 'self';\nobject-src 'none';";
	}

	public static function recommended_csp_scanner_friendly() {
		// Extremely permissive enforced CSP to satisfy scanners with minimal break risk.
		// This is NOT a "secure" CSP; it's a compatibility mode.
		return "default-src * data: blob: 'unsafe-inline' 'unsafe-eval';\nimg-src * data: blob:;\nscript-src * data: blob: 'unsafe-inline' 'unsafe-eval';\nstyle-src * data: blob: 'unsafe-inline';\nfont-src * data: blob:;\nconnect-src *;\nframe-src *;\nmedia-src * data: blob:;\nobject-src 'none';\nbase-uri 'self';";
	}

	public static function defaults() {
		return [
			// Minimal yes/no settings
			'enabled'              => true,
			'enable_hsts'          => false,
			'enable_csp'           => true,  // CSP defaults to Report-Only to avoid breakage
			'enable_csp_enforce_compat' => false, // Enforced CSP (very permissive) to satisfy scanners
			'enable_upgrade_https' => false, // adds upgrade-insecure-requests to CSP
			'harden_target_blank'  => true,
			'dashboard_menu_position' => 'top', // top | bottom

			// HSTS details (kept internal; no UI to reduce complexity)
			'hsts_max_age'    => 31536000,
			'hsts_subdomains' => true,
			'hsts_preload'    => false,
		];
	}

	public static function get() {
		$raw = get_option( SHF_OPTION_KEY, [] );
		if ( ! is_array( $raw ) ) {
			$raw = [];
		}
		$merged = array_merge( self::defaults(), $raw );

		// Back-compat: if older settings exist, map them into the minimal model.
		if ( isset( $raw['enabled_frontend'] ) || isset( $raw['enabled_rest'] ) || isset( $raw['enabled_admin'] ) ) {
			$any_apply = ! empty( $raw['enabled_frontend'] ) || ! empty( $raw['enabled_rest'] ) || ! empty( $raw['enabled_admin'] );
			$merged['enabled'] = $any_apply;
		}
		if ( isset( $raw['hsts_enabled'] ) ) {
			$merged['enable_hsts'] = ! empty( $raw['hsts_enabled'] );
		}
		if ( isset( $raw['csp_enabled'] ) ) {
			$merged['enable_csp'] = ! empty( $raw['csp_enabled'] );
		}
		if ( isset( $raw['csp_upgrade_insecure_requests'] ) ) {
			$merged['enable_upgrade_https'] = ! empty( $raw['csp_upgrade_insecure_requests'] );
		}

		return $merged;
	}

	public static function update( array $incoming ) {
		$defaults = self::defaults();
		$out      = [];

		$out['enabled']              = ! empty( $incoming['enabled'] );
		$out['enable_hsts']          = ! empty( $incoming['enable_hsts'] );
		$out['enable_csp']           = ! empty( $incoming['enable_csp'] );
		$out['enable_csp_enforce_compat'] = ! empty( $incoming['enable_csp_enforce_compat'] );
		$out['enable_upgrade_https'] = ! empty( $incoming['enable_upgrade_https'] );
		$out['harden_target_blank']  = ! empty( $incoming['harden_target_blank'] );

		$pos = sanitize_text_field( (string) ( $incoming['dashboard_menu_position'] ?? $defaults['dashboard_menu_position'] ) );
		$out['dashboard_menu_position'] = in_array( $pos, [ 'top', 'bottom' ], true ) ? $pos : $defaults['dashboard_menu_position'];

		// Preserve internal HSTS defaults (no UI, but keep stable).
		$out['hsts_max_age']    = $defaults['hsts_max_age'];
		$out['hsts_subdomains'] = $defaults['hsts_subdomains'];
		$out['hsts_preload']    = $defaults['hsts_preload'];

		return update_option( SHF_OPTION_KEY, $out );
	}
}

