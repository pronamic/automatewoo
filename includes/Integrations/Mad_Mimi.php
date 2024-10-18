<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Integration_Mad_Mimi
 * @since 2.7
 */
class Integration_Mad_Mimi extends Integration {

	/** @var string */
	public $integration_id = 'mad-mimi';

	/** @var string */
	private $username;

	/** @var string */
	private $api_key;

	/** @var string  */
	private $api_root = 'https://api.madmimi.com';

	/**
	 * @param $username
	 * @param $api_key
	 */
	function __construct( $username, $api_key ) {
		$this->username = $username;
		$this->api_key = $api_key;
	}

	/**
	 * The API details are not stored in the same way as other integrations as they are
	 * supplied in the Action settings so we do not need to test the integration at this point.
	 *
	 * @return bool True
	 */
	public function test_integration(): bool {
		return true;
	}

	/**
	 * The Mad Mimi Integration does not have a settings page and does not
	 * need to be enabled in order to use the related Actions
	 *
	 * @return bool True
	 */
	public function is_enabled(): bool {
		return true;
	}

	/**
	 * Automatically logs errors
	 *
	 * @param $method
	 * @param $endpoint
	 * @param $args
	 *
	 * @return Remote_Request
	 */
	function request( $method, $endpoint, $args = [] ) {
		$args['username'] = $this->username;
		$args['api_key'] = $this->api_key;

		$request_args = [
			'headers' => [
				'Accept' => 'application/json'
			],
			'timeout' => 10,
			'method' => $method,
			'sslverify' => false
		];

		$url = $this->api_root . $endpoint;
		// SEMGREP WARNING EXPLANATION
		// This is escaped with esc_url_raw, but semgrep only takes into consideration esc_url.
		$url = esc_url_raw( add_query_arg( $args, $url ) );

		$request = new Remote_Request( $url, $request_args );

		$this->maybe_log_request_errors( $request );

		return $request;
	}


	function build_csv($arr) {
		$csv = "";
		$keys = array_keys($arr);
		foreach ($keys as $key => $value) {
			$value = esc_attr($value);
			$csv .= $value . ",";
		}
		$csv = substr($csv, 0, -1);
		$csv .= "\n";
		foreach ($arr as $key => $value) {
			$value = esc_attr($value);
			$csv .= $value . ",";
		}
		$csv = substr($csv, 0, -1);
		$csv .= "\n";
		return $csv;
	}


}
