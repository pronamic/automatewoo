<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Integration_Bitly
 * @since 3.9
 */
class Integration_Bitly extends Integration {

	/** @var string */
	public $integration_id = 'bitly';

	/** @var string */
	private $api_key;

	/** @var string */
	private $api_base_url = 'https://api-ssl.bitly.com/v4';

	/**
	 * @param string $api_key
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Test the current API key to confirm that AutomateWoo can communicate with the Bitly API
	 *
	 * @return bool
	 */
	public function test_integration(): bool {
		return $this->request( 'GET', '/user' )->is_successful();
	}

	/**
	 * Check if the integration is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) Options::bitly_enabled();
	}

	/**
	 * @param string $long_url
	 * @param bool   $ignore_cache
	 *
	 * @return string|false
	 */
	public function shorten_url( $long_url, $ignore_cache = false ) {
		$cache_key = md5( $long_url );
		$cache     = Cache::get( $cache_key, 'bitly' );
		if ( ! $ignore_cache && $cache ) {
			return $cache;
		}

		$request = $this->request(
			'POST',
			'/shorten',
			[
				'long_url' => esc_url_raw( $long_url ),
			]
		);

		if ( false === $request || ! $request->is_successful() ) {
			return false;
		}

		$body      = $request->get_body();
		$short_url = esc_url_raw( $body['link'] );

		Cache::set( $cache_key, $short_url, 'bitly' );

		return apply_filters( 'automatewoo/bitly/shorten_url', $short_url, $long_url );
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function shorten_urls_in_text( $text ) {
		$replacer = new Replace_Helper( $text, [ $this, 'shorten_url' ], 'text_urls' );
		return $replacer->process();
	}

	/**
	 * @param string $method
	 * @param string $endpoint
	 * @param array  $args
	 *
	 * @return Remote_Request|false
	 */
	public function request( $method, $endpoint, $args = [] ) {
		$method       = strtoupper( $method );
		$request_args = [
			'timeout'   => 10,
			'method'    => $method,
			'sslverify' => false,
			'headers'   => [
				'Authorization' => "Bearer {$this->api_key}",
			],
		];

		$url = "{$this->api_base_url}{$endpoint}";

		switch ( $method ) {
			case 'GET':
				// SEMGREP WARNING EXPLANATION
				// This is escaped with esc_url_raw, but semgrep only takes into consideration esc_url.
				$url = esc_url_raw( add_query_arg( array_map( 'urlencode', $args ), $url ) );
				break;

			case 'POST':
				$json = wp_json_encode( $args );
				if ( false === $json ) {
					return false;
				} else {
					$request_args['body'] = $json;
				}

				$request_args['headers']['Content-Type'] = 'application/json';
				break;

			default:
				return false;
		}

		$request = new Remote_Request( $url, $request_args );
		$this->maybe_log_request_errors( $request );

		return $request;
	}
}
