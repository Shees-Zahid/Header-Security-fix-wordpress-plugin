<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SHF_Link_Hardener {
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'the_content', [ $this, 'harden_content_links' ], 20 );
		add_filter( 'widget_text', [ $this, 'harden_content_links' ], 20 );
		add_filter( 'widget_text_content', [ $this, 'harden_content_links' ], 20 );
	}

	public function harden_content_links( $content ) {
		$settings = SHF_Settings::get();
		if ( empty( $settings['harden_target_blank'] ) ) {
			return $content;
		}
		// Avoid heavy DOM parsing inside wp-admin/Elementor editor/preview.
		if ( is_admin() ) {
			return $content;
		}
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			try {
				if ( class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
					return $content;
				}
				if ( class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance->preview ) && method_exists( \Elementor\Plugin::$instance->preview, 'is_preview_mode' ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
					return $content;
				}
			} catch ( \Throwable $e ) {
				// If Elementor internals change, fail open (no hardening) rather than slowing/breaking rendering.
				return $content;
			}
		}
		if ( ! is_string( $content ) || $content === '' ) {
			return $content;
		}
		if ( stripos( $content, '<a' ) === false || stripos( $content, 'target=' ) === false ) {
			return $content;
		}

		$previous = libxml_use_internal_errors( true );
		$dom      = new DOMDocument();

		// Wrap in a container; ensure UTF-8.
		$html = '<!doctype html><html><head><meta charset="utf-8"></head><body><div id="shf-root">' . $content . '</div></body></html>';
		$loaded = $dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return $content;
		}

		$root = $dom->getElementById( 'shf-root' );
		if ( ! $root ) {
			return $content;
		}

		$changed = false;
		$links   = $root->getElementsByTagName( 'a' );

		// DOMNodeList is live; iterate by index.
		for ( $i = 0; $i < $links->length; $i++ ) {
			$a = $links->item( $i );
			if ( ! $a instanceof DOMElement ) {
				continue;
			}

			$target = $a->getAttribute( 'target' );
			if ( strtolower( trim( $target ) ) !== '_blank' ) {
				continue;
			}

			$rel = trim( (string) $a->getAttribute( 'rel' ) );
			$rels = preg_split( '/\s+/', $rel === '' ? '' : $rel );
			$rels = array_filter( array_map( 'strtolower', (array) $rels ) );

			$wanted = [ 'noopener', 'noreferrer' ];
			$new_rels = array_unique( array_merge( $rels, $wanted ) );
			sort( $new_rels );

			$new_rel_str = trim( implode( ' ', $new_rels ) );
			if ( $new_rel_str !== $rel ) {
				$a->setAttribute( 'rel', $new_rel_str );
				$changed = true;
			}
		}

		if ( ! $changed ) {
			return $content;
		}

		$out = '';
		foreach ( $root->childNodes as $child ) {
			$out .= $dom->saveHTML( $child );
		}
		return $out;
	}
}

