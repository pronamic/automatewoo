<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Integration_Campaign_Monitor
 * @since 3.0
 */
class Integration_Campaign_Monitor extends Integration {

	/** @var string */
	public $integration_id = 'campaign-monitor';

	/** @var string */
	private $api_key;

	/** @var string */
	private $client_id;

	/** @var string  */
	private $api_root = 'https://api.createsend.com/api/v3.1';


	/**
	 * @param string       $api_key
	 * @param string|false $client_id client ID is not required to support legacy action
	 */
	public function __construct( $api_key, $client_id = false ) {
		$this->api_key   = $api_key;
		$this->client_id = $client_id;
	}

	/**
	 * Test the current API key to confirm that AutomateWoo can communicate with the Campaign Monitor API
	 *
	 * @return bool
	 */
	public function test_integration(): bool {
		return $this->request( 'GET', '/primarycontact.json' )->is_successful();
	}

	/**
	 * Check if the integration is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) Options::campaign_monitor_enabled();
	}

	/**
	 * Automatically logs errors
	 *
	 * @param string $method
	 * @param string $endpoint
	 * @param array  $args
	 *
	 * @return Remote_Request
	 */
	public function request( $method, $endpoint, $args = [] ) {
		$request_args = [
			'headers'   => [
				'Authorization' => 'Basic ' . base64_encode( $this->api_key . ':x' ),
				'Accept'        => 'application/json',
			],
			'timeout'   => 10,
			'method'    => $method,
			'sslverify' => false,
		];

		$url = $this->api_root . $endpoint;

		switch ( $method ) {
			case 'GET':
			case 'DELETE':
				// SEMGREP WARNING EXPLANATION
				// This is escaped with esc_url_raw, but semgrep only takes into consideration esc_url.
				$url = esc_url_raw( add_query_arg( array_map( 'urlencode', $args ), $url ) );
				break;

			default:
				$request_args['body'] = wp_json_encode( $args );
				break;
		}

		$request = new Remote_Request( $url, $request_args );

		$this->maybe_log_request_errors( $request );

		return $request;
	}


	/**
	 * @return array
	 */
	public function get_lists() {
		if ( ! $this->client_id ) {
			return [];
		}

		$cache = Cache::get_transient( 'campaign_monitor_lists' );
		if ( $cache ) {
			return $cache;
		}

		$request = $this->request( 'GET', "/clients/{$this->client_id}/lists.json" );
		$lists   = $request->get_body();
		$clean   = [];

		if ( ! $request->is_successful() ) {
			return [];
		}

		foreach ( $lists as $list ) {
			$clean[ $list['ListID'] ] = $list['Name'];
		}

		Cache::set_transient( 'campaign_monitor_lists', $clean, 0.15 );

		return $clean;
	}


	/**
	 * Clear cached data
	 */
	public function clear_cache_data() {
		Cache::delete_transient( 'campaign_monitor_lists' );
	}
}
