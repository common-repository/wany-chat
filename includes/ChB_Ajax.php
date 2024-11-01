<?php


namespace ChatBot;


class ChB_Ajax {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'do_wy_ajax' ), 0 );
		self::add_ajax_events();
	}

	public static function define_ajax() {
		// phpcs:disable
		if ( ! empty( $_GET['wy-ajax'] ) ) {
			if ( ! defined( 'DOING_AJAX' ) ) {
				define( 'DOING_AJAX', true );
			}

			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
			}
			$GLOBALS['wpdb']->hide_errors();
		}
		// phpcs:enable
	}

	private static function wy_ajax_headers() {
		if ( ! headers_sent() ) {
			send_origin_headers();
			send_nosniff_header();
			wc_nocache_headers();
			header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
		} elseif ( WP_DEBUG ) {
			headers_sent( $file, $line );
			trigger_error( "wc_ajax_headers cannot set headers - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Check for WC Ajax request and fire action.
	 */
	public static function do_wy_ajax() {
		global $wp_query;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['wy-ajax'] ) ) {
			$wp_query->set( 'wy-ajax', sanitize_text_field( wp_unslash( $_GET['wy-ajax'] ) ) );
		}

		$action = $wp_query->get( 'wy-ajax' );

		if ( $action ) {
			self::wy_ajax_headers();
			$action = sanitize_text_field( $action );
			do_action( 'wy_ajax_' . $action );
			wp_die();
		}
		// phpcs:enable
	}

	public static function add_ajax_events() {
		add_action( 'wy_ajax_set_fb_user', [ __CLASS__, 'set_fb_user' ] );
	}

	public static function set_fb_user() {
		if ( ! check_ajax_referer( 'set_fb_user', '_wy_nonce' ) ) {
			wp_die( - 1, 403 );
		}

		wp_send_json( ChB_User::setMCUser4WooSession_API() );
	}

}
