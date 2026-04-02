<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SHF_Headers {
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'send_headers', [ $this, 'send_security_headers' ], 1 );
	}

	private function should_apply_for_request( array $settings ) {
		return ! empty( $settings['enabled'] );
	}

	private function header_name_exists( $name ) {
		$name = strtolower( trim( $name ) );
		foreach ( headers_list() as $h ) {
			$pos = strpos( $h, ':' );
			if ( $pos === false ) {
				continue;
			}
			$existing = strtolower( trim( substr( $h, 0, $pos ) ) );
			if ( $existing === $name ) {
				return true;
			}
		}
		return false;
	}

	public function send_security_headers() {
		if ( headers_sent() ) {
			return;
		}

		$settings = SHF_Settings::get();
		if ( ! $this->should_apply_for_request( $settings ) ) {
			return;
		}

		// Legacy scanner headers (safe defaults)
		// X-XSS-Protection is deprecated; set to 0 to avoid legacy XSS filter bugs.
		if ( ! $this->header_name_exists( 'X-XSS-Protection' ) ) {
			header( 'X-XSS-Protection: 0' );
		}

		// Prevent Adobe Flash/Acrobat cross-domain policy files being used.
		if ( ! $this->header_name_exists( 'X-Permitted-Cross-Domain-Policies' ) ) {
			header( 'X-Permitted-Cross-Domain-Policies: none' );
		}

		// Feature-Policy is deprecated in favor of Permissions-Policy, but some scanners still check it.
		if ( ! $this->header_name_exists( 'Feature-Policy' ) ) {
			header( "Feature-Policy: accelerometer 'none'; ambient-light-sensor 'none'; autoplay 'none'; battery 'none'; camera 'none'; display-capture 'none'; encrypted-media 'none'; geolocation 'none'; gyroscope 'none'; magnetometer 'none'; microphone 'none'; midi 'none'; payment 'none'; picture-in-picture 'none'; usb 'none'; vr 'none'; wake-lock 'none'" );
		}

		// Permissions-Policy (reduce access to powerful features by default)
		if ( ! $this->header_name_exists( 'Permissions-Policy' ) ) {
			header( "Permissions-Policy: accelerometer=(), ambient-light-sensor=(), autoplay=(), battery=(), camera=(), cross-origin-isolated=(), display-capture=(), encrypted-media=(), execution-while-not-rendered=(), execution-while-out-of-viewport=(), fullscreen=*, geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), picture-in-picture=(), publickey-credentials-get=(), screen-wake-lock=(), sync-xhr=(), usb=(), web-share=(), xr-spatial-tracking=()" );
		}

		// X-Content-Type-Options (always on when enabled)
		if ( ! $this->header_name_exists( 'X-Content-Type-Options' ) ) {
			header( 'X-Content-Type-Options: nosniff' );
		}

		// Referrer-Policy (fixed safe default)
		if ( ! $this->header_name_exists( 'Referrer-Policy' ) ) {
			header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		}

		// HSTS (only on HTTPS; avoid localhost/dev)
		if ( ! empty( $settings['enable_hsts'] ) && is_ssl() && ! $this->header_name_exists( 'Strict-Transport-Security' ) ) {
			$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
			$ends_with_local = false;
			if ( $host !== '' ) {
				$suffix = '.local';
				$ends_with_local = strlen( $host ) >= strlen( $suffix ) && substr( $host, -strlen( $suffix ) ) === $suffix;
			}
			if ( $host && ! in_array( $host, [ 'localhost', '127.0.0.1', '::1' ], true ) && ! $ends_with_local ) {
				$value = 'max-age=' . (int) $settings['hsts_max_age'];
				if ( ! empty( $settings['hsts_subdomains'] ) ) {
					$value .= '; includeSubDomains';
				}
				if ( ! empty( $settings['hsts_preload'] ) ) {
					$value .= '; preload';
				}
				header( 'Strict-Transport-Security: ' . $value );
			}
		}

		// CSP
		if ( ! empty( $settings['enable_csp'] ) ) {
			$directives = trim( (string) SHF_Settings::recommended_csp() );
			if ( ! empty( $settings['enable_upgrade_https'] ) ) {
				$directives .= ( $directives === '' ? '' : "\n" ) . 'upgrade-insecure-requests;';
			}

			$directives = $this->normalize_csp( $directives );

			if ( $directives !== '' ) {
				// Use Report-Only by default to avoid breaking Elementor/admin.
				if ( ! $this->header_name_exists( 'Content-Security-Policy-Report-Only' ) ) {
					header( 'Content-Security-Policy-Report-Only: ' . $directives );
				}
			}
		}

		// Enforced CSP (compat mode) - only on frontend to reduce admin break risk.
		if ( ! is_admin() && ! empty( $settings['enable_csp_enforce_compat'] ) ) {
			$directives = trim( (string) SHF_Settings::recommended_csp_scanner_friendly() );
			if ( ! empty( $settings['enable_upgrade_https'] ) ) {
				$directives .= ( $directives === '' ? '' : "\n" ) . 'upgrade-insecure-requests;';
			}
			$directives = $this->normalize_csp( $directives );
			if ( $directives !== '' && ! $this->header_name_exists( 'Content-Security-Policy' ) ) {
				header( 'Content-Security-Policy: ' . $directives );
			}
		}

		// X-Frame-Options
		// Screaming Frog flags this explicitly; set it even if CSP has frame-ancestors.
		if ( ! $this->header_name_exists( 'X-Frame-Options' ) ) {
			header( 'X-Frame-Options: SAMEORIGIN' );
		}
	}

	private function normalize_csp( $directives ) {
		// Convert multi-line textarea into a compact single header string.
		$lines = preg_split( "/\r\n|\r|\n/", (string) $directives );
		$out   = [];
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( $line === '' ) {
				continue;
			}
			$out[] = preg_replace( '/\s+/', ' ', $line );
		}
		return trim( implode( ' ', $out ) );
	}
}

