<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Fixed-window request counter with a per-IP limit and a site-wide ceiling.
 *
 * Counts are approximate: they live in transients and the read-modify-write is
 * not atomic, so concurrent requests can overshoot a limit.
 *
 * @since 6.9.3
 * @internal
 */
class Rate_Limiter {

	/** Option holding the most recent throttle: bucket, scope and time. */
	public const LAST_THROTTLED_OPTION = 'automatewoo_rate_limiter_last_throttled';

	/**
	 * Count the current request against a bucket and report whether it is over a limit.
	 *
	 * The per-IP counter is checked first. A request refused there is not counted
	 * toward the site-wide ceiling. A limit of 0 or less disables that counter, and
	 * the per-IP counter is also skipped when the client address is unknown.
	 *
	 * @param string $bucket       Bucket name, used in the transient keys.
	 * @param int    $limit        Maximum requests per IP within the window.
	 * @param int    $window       Window length in seconds.
	 * @param int    $global_limit Maximum requests from all IPs within the window.
	 * @param string $log_context  Logger handle used for the once-per-window notice.
	 * @return bool True when the request is over a limit.
	 */
	public static function is_over_limit( string $bucket, int $limit, int $window, int $global_limit, string $log_context ): bool {
		$ip = self::get_client_ip();

		if ( $ip && $limit > 0 && self::count_and_check( 'automatewoo_' . $bucket . '_throttle_' . md5( $ip ), $limit, $window, $log_context, 'per-source', $bucket ) ) {
			return true;
		}

		return $global_limit > 0 && self::count_and_check( 'automatewoo_' . $bucket . '_throttle_global', $global_limit, $window, $log_context, 'site-wide', $bucket );
	}

	/**
	 * Get the address of the connecting client.
	 *
	 * Uses the real connection address, never client-sent forwarding headers.
	 *
	 * @return string Empty string when no valid address is available.
	 */
	public static function get_client_ip(): string {
		$ip = '';

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$remote = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			$ip     = false !== filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '';
		}

		/**
		 * Filters the client IP address used for rate limiting.
		 *
		 * Stores behind a trusted CDN or proxy can return the forwarded address,
		 * e.g. `WC_Geolocation::get_ip_address()`.
		 *
		 * @since 6.9.3
		 *
		 * @param string $ip The connecting address, or an empty string if unavailable.
		 */
		return (string) apply_filters( 'automatewoo/rate_limiter/client_ip', $ip );
	}

	/**
	 * @param string $key         Transient key.
	 * @param int    $limit       Maximum count within the window.
	 * @param int    $window      Window length in seconds.
	 * @param string $log_context Logger handle.
	 * @param string $scope       Label for the log message.
	 * @param string $bucket      Bucket name, recorded as the last throttle.
	 * @return bool True when the counter is already at its limit.
	 */
	private static function count_and_check( string $key, int $limit, int $window, string $log_context, string $scope, string $bucket ): bool {
		$state = (array) get_transient( $key );

		// The elapsed check, not the transient TTL, defines the window, so the
		// window stays anchored to its first request.
		if ( empty( $state['start'] ) || ( time() - (int) $state['start'] ) >= $window ) {
			$state = [
				'start' => time(),
				'count' => 0,
			];
		}

		if ( $state['count'] >= $limit ) {
			// A sustained source would otherwise flood the log with an entry per request.
			if ( empty( $state['logged'] ) ) {
				Logger::notice( $log_context, sprintf( 'A request was throttled by the %s limit.', $scope ) );
				update_option(
					self::LAST_THROTTLED_OPTION,
					[
						'bucket' => $bucket,
						'scope'  => $scope,
						'time'   => time(),
					],
					false
				);
				$state['logged'] = true;
				set_transient( $key, $state, $window );
			}

			return true;
		}

		++$state['count'];
		// The TTL is only a garbage-collection backstop.
		set_transient( $key, $state, $window );

		return false;
	}
}
