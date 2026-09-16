<?php

namespace AutomateWoo;

/**
 * Abstract for API integration classes.
 *
 * @class Integration
 * @since 2.3
 */
abstract class Integration {

	/** @var string */
	public $integration_id;

	/** @var bool */
	public $log_errors = true;

	/**
	 * Keys whose values should be masked before they are written to a log.
	 *
	 * Matching is exact and case-insensitive, never a substring match, so that
	 * keys such as `monkey` or `token_count` are left alone.
	 *
	 * @since x.x.x
	 *
	 * @var string[]
	 */
	protected $sensitive_log_keys = [
		'api_key',
		'apikey',
		'key',
		'api_token',
		'token',
		'access_token',
		'refresh_token',
		'client_secret',
		'signature',
		'sig',
		'username',
		'password',
		'auth',
		'secret',
	];

	/**
	 * Placeholder used to mask a sensitive value.
	 *
	 * @since x.x.x
	 *
	 * @var string
	 */
	const LOG_REDACTED_VALUE = '[redacted]';

	/**
	 * Add a log entry.
	 *
	 * @param string $message The message to log.
	 *
	 * @return void
	 */
	public function log( $message ): void {
		if ( ! $this->log_errors ) {
			return;
		}

		Logger::info( 'integration-' . $this->integration_id, $message );
	}


	/**
	 * Maybe log the results of a request.
	 *
	 * @param Remote_Request $request The request object to maybe log.
	 *
	 * @return void
	 */
	public function maybe_log_request_errors( $request ): void {
		if ( ! $this->log_errors ) {
			return;
		}

		if ( $request->is_http_error() ) {
			$this->log( $request->get_http_error_message() );
		} elseif ( $request->is_api_error() ) {
			$this->log(
				$request->get_response_code() . ' ' . $request->get_response_message()
				. '. Method: ' . $request->method
				. '. Endpoint: ' . $this->redact_log_url( $request->url )
				. '. Response body: ' . print_r( $this->redact_log_data( $request->get_body() ), true )  // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			);
		}
	}

	/**
	 * Mask the values of sensitive query-string parameters in a URL before logging it.
	 *
	 * Any userinfo in the URL (e.g. `https://user:pass@host/...`) is always stripped,
	 * whether or not a query string is present. If the URL cannot be parsed, a fixed
	 * placeholder is returned instead of the original URL so a malformed URL can never
	 * leak an unredacted secret.
	 *
	 * Note: rebuilding the query string via parse_str()/http_build_query() normalizes
	 * it: repeated params collapse (e.g. `?id=1&id=2` becomes `id=2`). This is an
	 * accepted residual for logging purposes only.
	 *
	 * @since x.x.x
	 *
	 * @param string $url The URL to redact.
	 *
	 * @return string
	 */
	protected function redact_log_url( string $url ): string {
		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) ) {
			return '[unparsable URL]';
		}

		// Userinfo (e.g. https://user:pass@host/...) may contain credentials, so it must
		// never be logged, with or without a query string.
		unset( $parts['user'], $parts['pass'] );

		if ( empty( $parts['query'] ) ) {
			return $this->build_url_from_parts( $parts );
		}

		parse_str( $parts['query'], $query_args );

		$query_args = $this->redact_log_data( $query_args );

		// http_build_query() percent-encodes the placeholder (e.g. `%5Bredacted%5D`);
		// decode just that token back to the readable placeholder for a legible log line.
		$parts['query'] = str_replace(
			rawurlencode( self::LOG_REDACTED_VALUE ),
			self::LOG_REDACTED_VALUE,
			http_build_query( $query_args )
		);

		return $this->build_url_from_parts( $parts );
	}

	/**
	 * Rebuild a URL string from the components returned by wp_parse_url().
	 *
	 * @since x.x.x
	 *
	 * @param array $parts URL components, as returned by wp_parse_url().
	 *
	 * @return string
	 */
	private function build_url_from_parts( array $parts ): string {
		$url  = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
		$url .= isset( $parts['host'] ) ? $parts['host'] : '';
		$url .= isset( $parts['port'] ) ? ':' . $parts['port'] : '';
		$url .= isset( $parts['path'] ) ? $parts['path'] : '';
		$url .= ! empty( $parts['query'] ) ? '?' . $parts['query'] : '';
		$url .= isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

		return $url;
	}

	/**
	 * Recursively mask the values of sensitive keys in an array before logging it.
	 *
	 * Non-array input (e.g. null or false, as returned by Remote_Request::get_body()
	 * for a non-JSON body or an HTTP error) is passed through unchanged.
	 *
	 * @since x.x.x
	 *
	 * @param mixed $data The data to redact.
	 *
	 * @return mixed
	 */
	protected function redact_log_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		foreach ( $data as $key => $value ) {
			if ( $this->is_sensitive_log_key( $key ) ) {
				$data[ $key ] = self::LOG_REDACTED_VALUE;
			} elseif ( is_array( $value ) ) {
				$data[ $key ] = $this->redact_log_data( $value );
			}
		}

		return $data;
	}

	/**
	 * Check if a key should be treated as sensitive for logging purposes.
	 *
	 * Matching is exact and case-insensitive, never a substring match.
	 *
	 * @since x.x.x
	 *
	 * @param int|string $key The array/query-string key to check.
	 *
	 * @return bool
	 */
	private function is_sensitive_log_key( $key ): bool {
		return in_array( strtolower( (string) $key ), array_map( 'strtolower', $this->sensitive_log_keys ), true );
	}

	/**
	 * Test if the current API config is valid.
	 *
	 * @return bool True if the integration can communicate with external API or false otherwise
	 */
	abstract public function test_integration(): bool;

	/**
	 * Check if the integration is enabled.
	 *
	 * @return bool True if the integration is enabled.
	 */
	abstract public function is_enabled(): bool;
}
