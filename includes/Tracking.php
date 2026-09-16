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
		$log = $workflow->get_current_log();

		// SEMGREP WARNING EXPLANATION
		// This URL is escaped later
		$url = add_query_arg(
			[
				'aw-action' => 'open',
				'log'       => $log ? $log->get_id() : 0,
			],
			home_url()
		);

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

		$log = $workflow->get_current_log();

		$args = [
			'aw-action' => 'click',
			'log'       => $log ? $log->get_id() : 0,
			'redirect'  => urlencode( $valid_redirect ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- Preserve redirect URL encoding.
		];

		// SEMGREP WARNING EXPLANATION
		// URL is escaped later by the consumer.
		$url = add_query_arg( $args, home_url() );

		return apply_filters( 'automatewoo_click_track_url', $url, $args );
	}


	/**
	 * Records the open track event if a valid log id is passed.
	 * Then outputs a blank GIF image.
	 */
	public static function handle_open_tracking_url() {
		$log = Log_Factory::get( aw_request( 'log' ) );

		if ( $log && ! self::is_excluded_user_agent() && ! self::is_log_customer_opted_out( $log ) ) {
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
		$redirect = esc_url_raw( aw_request( 'redirect' ) );
		$log      = Log_Factory::get( aw_request( 'log' ) );

		if ( ! $redirect ) {
			return;
		}

		if ( $log && ! self::is_excluded_user_agent() && ! self::is_log_customer_opted_out( $log ) ) {
			$log->record_click( $redirect );
		}

		// fallback to the home page instead of the admin area if redirect is unsafe
		add_filter( 'wp_safe_redirect_fallback', [ 'AutomateWoo\Tracking', 'safe_redirect_fallback' ] );

		wp_safe_redirect( $redirect );
		exit;
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
	 * @since x.x.x
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
	 * @since x.x.x
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
