<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contains functions relevant to open, click, conversion tracking.
 *
 * @since 3.9
 */
class Tracking {


	/**
	 * @param Workflow $workflow
	 * @return string
	 */
	public static function get_open_tracking_url( $workflow ) {
		// SEMGREP WARNING EXPLANATION
		// This URL is escaped later
		$url = add_query_arg( self::get_tracking_url_args( 'open', $workflow->get_current_log() ), home_url() );

		return apply_filters( 'automatewoo_open_track_url', $url, $workflow );
	}


	/**
	 * @param Workflow $workflow
	 * @param string   $redirect
	 * @return string
	 */
	public static function get_click_tracking_url( $workflow, $redirect ) {
		$valid_redirect = wp_validate_redirect( $redirect );

		if ( ! $valid_redirect ) {
			return $redirect; // if redirect is not a valid redirect return the original URL
		}

		$args             = self::get_tracking_url_args( 'click', $workflow->get_current_log() );
		$args['redirect'] = urlencode( $valid_redirect ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- Preserve redirect URL encoding.

		// SEMGREP WARNING EXPLANATION
		// URL is escaped later by the consumer.
		$url = add_query_arg( $args, home_url() );

		return apply_filters( 'automatewoo_click_track_url', $url, $args );
	}


	/**
	 * Gets the query args that identify a log in its tracking URLs.
	 *
	 * @since 6.9.3
	 *
	 * @param string   $action The tracking action, open or click.
	 * @param Log|bool $log    The log of the current workflow run, or false when there is none.
	 *
	 * @return array
	 */
	private static function get_tracking_url_args( string $action, $log ): array {
		$args = [
			'aw-action' => $action,
			'log'       => $log ? $log->get_id() : 0,
		];

		$token = $log ? $log->get_tracking_token() : '';

		if ( '' !== $token ) {
			$args['token'] = $token;
		}

		return $args;
	}


	/**
	 * Records an open on the log of the request, when the log records the request.
	 * Then outputs a blank GIF image.
	 *
	 * @see Tracking::get_log_to_record_on()
	 */
	public static function handle_open_tracking_url() {
		$log = self::get_log_to_record_on();

		if ( $log ) {
			$log->record_open();
		}

		$image_path = AW()->admin_path( '/assets/img/blank.gif' );

		// render image
		header( 'Content-Type: image/gif' );
		header( 'Pragma: public' ); // required
		header( 'Expires: 0' ); // no cache
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', false );
		header( 'Content-Disposition: attachment; filename="blank.gif"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . filesize( $image_path ) ); // provide file size
		readfile( $image_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- readfile streams the tracking-pixel image to output.
		exit;
	}


	/**
	 * Records the click event and then redirects the user if safe.
	 * Still allow redirect if log param is invalid, when testing a '0' value for log is used.
	 */
	public static function handle_click_tracking_url() {
		// Only the query string is read, which bounds the stored URL by the length of
		// the request line. Tracked links always carry the redirect there.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A tracked link is opened from an email or text message, so it cannot carry a nonce.
		$redirect = isset( $_GET['redirect'] ) && is_string( $_GET['redirect'] ) ? esc_url_raw( wp_unslash( $_GET['redirect'] ) ) : '';

		if ( ! $redirect ) {
			return;
		}

		$log = self::get_log_to_record_on();

		if ( $log ) {
			$log->record_click( $redirect );
		}

		// fallback to the home page instead of the admin area if redirect is unsafe
		add_filter( 'wp_safe_redirect_fallback', [ 'AutomateWoo\Tracking', 'safe_redirect_fallback' ] );

		wp_safe_redirect( $redirect );
		exit;
	}


	/**
	 * Gets the log that the current tracking request records an open or a click on.
	 *
	 * Tracking links are only sent for a log with tracking enabled, so no other log records
	 * anything. A log that has a tracking token records only requests that carry the token.
	 * A log from before tokens were stored has none and is found by its ID alone, so the
	 * links in messages that were already sent keep recording.
	 *
	 * @since 6.9.3
	 *
	 * @return Log|false False when nothing is recorded for the request.
	 */
	private static function get_log_to_record_on() {
		$log = Log_Factory::get( aw_request( 'log' ) );

		if ( ! $log || ! $log->is_tracking_enabled() || ! self::is_request_token_valid_for_log( $log ) ) {
			return false;
		}

		if ( self::is_excluded_user_agent() || self::is_log_customer_opted_out( $log ) ) {
			return false;
		}

		return $log;
	}


	/**
	 * Does the current request carry the tracking token of the log?
	 *
	 * @since 6.9.3
	 *
	 * @param Log $log
	 *
	 * @return bool True also when the log has no token.
	 */
	private static function is_request_token_valid_for_log( Log $log ): bool {
		$token = $log->get_tracking_token();

		if ( '' === $token ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A tracked link is opened from an email or text message, so it cannot carry a nonce.
		$request_token = isset( $_GET['token'] ) && is_string( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		return hash_equals( $token, $request_token );
	}


	/**
	 * @return string
	 */
	public static function safe_redirect_fallback() {
		return apply_filters( 'automatewoo/click_track/safe_redirect_fallback', home_url() );
	}


	/**
	 * Has the recipient this log belongs to opted out of tracking?
	 *
	 * Defence in depth: emails already delivered still carry pixels and tracked links,
	 * and those requests must stop being recorded once the recipient has opted out. The
	 * pixel and the redirect keep working, so nothing looks broken to the recipient.
	 *
	 * @internal
	 * @since 6.9.1
	 *
	 * @param Log $log
	 *
	 * @return bool
	 */
	public static function is_log_customer_opted_out( $log ) {
		$data_layer = $log->get_data_layer( 'object' );

		if ( ! $data_layer ) {
			return false;
		}

		$customer = $data_layer->get_customer();

		return $customer && $customer->is_tracking_opted_out();
	}

	/**
	 * Should this URL be left alone by click tracking?
	 *
	 * Covers the unsubscribe link and the tracking opt-out link: recording a click on a
	 * link whose whole purpose is to stop tracking would defeat it.
	 *
	 * @since 6.9.1
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function is_url_excluded_from_click_tracking( $url ) {
		$excluded = (bool) strstr( $url, 'aw-action=unsubscribe' )
			|| (bool) strstr( $url, 'intent=' . Communication_Page::INTENT_TRACKING_OPT_OUT );

		return (bool) apply_filters( 'automatewoo/tracking/is_url_excluded_from_click_tracking', $excluded, $url );
	}

	/**
	 * Is the useragent excluded from tracking.
	 *
	 * @since 4.8.1
	 *
	 * @return bool
	 */
	public static function is_excluded_user_agent() {
		$user_agent = wc_get_user_agent();

		$matches = (array) apply_filters(
			'automatewoo/tracking/excluded_user_agents',
			[
				'bitlybot',
			]
		);

		foreach ( $matches as $match ) {
			// Match any part of the user agent string
			if ( false !== stristr( $user_agent, $match ) ) {
				return true;
			}
		}

		return false;
	}
}
