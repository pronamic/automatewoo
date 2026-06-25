<?php

namespace AutomateWoo;

/**
 * @class Ajax
 * @since 2.7
 */
class Ajax {

	/**
	 * Init
	 */
	public static function init() {
		self::maybe_define_ajax();
		add_action( 'template_redirect', [ __CLASS__, 'do_ajax' ], 0 );
	}


	/**
	 * @param  string $request Optional
	 * @return string
	 */
	public static function get_endpoint( $request ) {
		// SEMGREP WARNING EXPLANATION
		// $request seems to be always "%%endpoint%%" in the consumer side.
		return add_query_arg( 'aw-ajax', $request );
	}


	/**
	 * Set WC AJAX constant and headers.
	 */
	public static function maybe_define_ajax() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check for the AJAX endpoint flag, no state change.
		if ( empty( $_GET['aw-ajax'] ) ) {
			return;
		}

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		// Turn off display_errors during AJAX events to prevent malformed JSON
		if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.display_errors_Disallowed -- Intentional for the AJAX error path.
			@ini_set( 'display_errors', 0 );
		}

		$GLOBALS['wpdb']->hide_errors();
	}


	/**
	 * Send headers
	 */
	private static function send_headers() {
		send_origin_headers();
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Intentional for the AJAX header path; headers may already be sent.
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Intentional for the AJAX header path; headers may already be sent.
		@header( 'X-Robots-Tag: noindex' );
		send_nosniff_header();
		nocache_headers();
		status_header( 200 );
	}


	/**
	 * Check for AW Ajax request and fire action.
	 */
	public static function do_ajax() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check for the AJAX endpoint flag, no state change.
		if ( empty( $_GET['aw-ajax'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only AJAX dispatch; value sanitized, individual handlers verify nonce/capability.
		$action = sanitize_text_field( wp_unslash( $_GET['aw-ajax'] ) );
		if ( ! $action ) {
			return;
		}

		self::send_headers();
		do_action( 'automatewoo/ajax/' . sanitize_text_field( $action ) );
		wp_die();
	}


	/**
	 * @param mixed $data
	 */
	public static function send_json_success( $data = null ) {
		do_action( 'automatewoo/ajax/before_send_json' );
		wp_send_json_success( $data );
	}


	/**
	 * @param mixed $data
	 */
	public static function send_json_error( $data = null ) {
		do_action( 'automatewoo/ajax/before_send_json' );
		wp_send_json_error( $data );
	}
}
