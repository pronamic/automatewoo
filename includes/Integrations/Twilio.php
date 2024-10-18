<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Integration_Twilio
 * @since 3.9
 */
class Integration_Twilio extends Integration {

	/** @var string */
	public $integration_id = 'twilio';

	/** @var string */
	private $from_number;

	/** @var string */
	private $account_sid;

	/** @var string */
	private $auth_token;

	/** @var string  */
	private $api_root;


	function __construct( $from_number, $account_sid, $auth_token ) {
		$this->from_number = trim( $from_number );
		$this->account_sid = trim( $account_sid );
		$this->auth_token = trim( $auth_token );
		$this->api_root = 'https://api.twilio.com/2010-04-01/Accounts/' . $this->account_sid;
	}

	/**
	 * Test the current API key to confirm that AutomateWoo can communicate with the Twilio API
	 *
	 * @return bool
	 */
	public function test_integration(): bool {
		return $this->request( 'GET', '/' )->is_successful();
	}

	/**
	 * Check if the integration is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) Options::twilio_enabled();
	}

	/**
	 * @return string
	 */
	function get_from_number() {
		return $this->from_number;
	}


	/**
	 * @param string $to
	 * @param string $body
	 * @param bool|string $from
	 * @return Remote_Request
	 */
	function send_sms( $to, $body, $from = false ) {
		$args = [
			'To'   => $to,
			'From' => $from ? $from : $this->get_from_number(),
			'Body' => $body,
		];

		$request = $this->request('POST', '/Messages.json', $args );

		if ( AUTOMATEWOO_LOG_SENT_SMS && $request->is_successful() ) {
			Logger::info( 'sent-sms', print_r( $args, true ) );
		}

		return $request;
	}


	/**
	 * Pulls the error message from the Twilio API response or from the wp_error object.
	 *
	 * @param Remote_Request $request
	 * @return string
	 */
	function get_request_error_message( $request ) {
		if ( $request->is_http_error() ) {
			return $request->get_http_error_message();
		}
		else {
			$body = $request->get_body();
			return $body['message'];
		}
	}


	/**
	 * @param $method
	 * @param $endpoint
	 * @param $args
	 *
	 * @return Remote_Request
	 */
	function request( $method, $endpoint, $args = [] ) {
		$request_args = [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( $this->account_sid . ':' . $this->auth_token ),
				'Accept' => 'application/json'
			],
			'timeout' => 10,
			'method' => $method,
			'sslverify' => false
		];

		$request_args['body'] = http_build_query( $args );

		$request = new Remote_Request( $this->api_root . $endpoint, $request_args );

		$this->maybe_log_request_errors( $request );

		return $request;
	}


}
