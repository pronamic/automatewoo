<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Checks where a request came from.
 *
 * The is_same_site() method is the control that a request was made from the store’s own pages.
 *
 * @since 6.9.3
 * @internal
 */
class Request_Origin {

	/**
	 * Whether the current request uses the POST method.
	 *
	 * @return bool
	 */
	public static function is_post(): bool {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

		return 'POST' === $method;
	}

	/**
	 * Whether the Origin header, or the Referer header when there is no usable Origin,
	 * points at this site. Host and port are compared. The default ports 80 and 443 count as
	 * the same, so a store whose TLS is ended by a proxy still matches.
	 *
	 * @return bool False when neither header is present.
	 */
	public static function is_same_site(): bool {
		$source = '';

		foreach ( [ 'HTTP_ORIGIN', 'HTTP_REFERER' ] as $key ) {
			$value = isset( $_SERVER[ $key ] ) ? trim( sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) ) : '';

			if ( '' !== $value && 'null' !== $value ) {
				$source = $value;
				break;
			}
		}

		if ( '' === $source ) {
			return false;
		}

		$request_host = self::get_host_and_port( $source );

		return '' !== $request_host && in_array( $request_host, self::get_allowed_hosts(), true );
	}

	/**
	 * @return string[] Lower-cased host values, with a port when it is not 80 or 443.
	 */
	private static function get_allowed_hosts(): array {
		$hosts = [];

		foreach ( [ home_url(), site_url() ] as $url ) {
			$hosts[] = self::get_host_and_port( $url );
		}

		/**
		 * Filters extra hosts treated as this site when checking where a request came from.
		 *
		 * For multi-domain or proxied stores. Each entry is a host or a host:port string.
		 * A host without a port matches the default ports (80 and 443).
		 *
		 * @since 6.9.3
		 *
		 * @param string[] $allowed_hosts
		 */
		$extra = apply_filters( 'automatewoo/request_origin/allowed_hosts', [] );

		foreach ( (array) $extra as $entry ) {
			if ( ! is_string( $entry ) || '' === $entry ) {
				continue;
			}

			if ( preg_match( '/:\d+$/', $entry ) ) {
				$hosts[] = self::get_host_and_port( '//' . $entry );
			} else {
				$hosts[] = self::get_host_and_port( 'http://' . $entry );
				$hosts[] = self::get_host_and_port( 'https://' . $entry );
			}
		}

		return array_values( array_filter( array_unique( $hosts ) ) );
	}

	/**
	 * @param string $url
	 * @return string Lower-cased host, with the port added when it is not 80 or 443. Empty string when there is no host.
	 */
	private static function get_host_and_port( string $url ): string {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['host'] ) ) {
			return '';
		}

		$host = strtolower( $parts['host'] );

		if ( empty( $parts['port'] ) || in_array( (int) $parts['port'], [ 80, 443 ], true ) ) {
			return $host;
		}

		return $host . ':' . $parts['port'];
	}
}
