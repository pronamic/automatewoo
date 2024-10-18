<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Remote_Request
 * @since 2.3.1
 */
class Remote_Request {

	/** @var string */
	public $method;

	/** @var string */
	public $url;

	/** @var array */
	public $http_success_codes = [ 200, 201, 202, 203, 204 ];

	/**
	 * Response from wp_remote_request()
	 * @var array|\WP_Error
	 */
	public $request;


	/**
	 * Passes to wp_remote_request()
	 *
	 * @param $url
	 * @param $args
	 */
	function __construct( $url, $args ) {
		$domain = home_url();
		$domain = str_replace( [ 'http://', 'https://' ], '', $domain );
		$domain = untrailingslashit( $domain );

		$args = wp_parse_args( $args, [
			'user-agent' => 'AutomateWoo ' . AW()->version . ' - ' . $domain
		]);

		$this->url = $url;
		$this->method = $args['method'];

		$this->request = wp_remote_request( $url, $args );
	}

	/**
	 * Checks if the remote HTTP request failed.
	 *
	 * Note: This doesn't check the response, it only checks that some response was received.
	 *
	 * @return bool
	 */
	function is_http_error() {
		return is_wp_error( $this->request );
	}

	/**
	 * Checks if a valid HTTP response code was returned.
	 *
	 * @return bool
	 */
	function is_api_error() {
		if ( $this->is_http_error() ) {
			return false;
		}
		return ! $this->is_http_success_code();
	}

	/**
	 * Checks if the remote request was successful.
	 *
	 * Checks that the API returned a success HTTP response code e.g. 200.
	 *
	 * @return bool
	 */
	function is_successful() {
		return $this->is_http_success_code();
	}

	/**
	 * Returns the HTTP error message if the request failed and no response was received.
	 *
	 * @return string|false
	 */
	function get_http_error_message() {
		if ( $this->is_http_error() ) {
			return $this->request->get_error_message();
		}
		return false;
	}

	/**
	 * Returns the HTTP status code of the request.
	 *
	 * Returns 503 if the request failed.
	 *
	 * @return int
	 */
	function get_response_code() {
		if ( $this->is_http_error() ) {
			return 503;
		}

		return $this->request['response']['code'];
	}

	/**
	 * Returns the HTTP request response message.
	 *
	 * @return string
	 */
	function get_response_message() {
		if ( $this->is_http_error() ) {
			return '';
		}

		return $this->request['response']['message'];
	}

	/**
	 * Returns the processed request body.
	 *
	 * JSON will be decoded.
	 *
	 * @return array|false
	 */
	function get_body() {
		if ( $this->is_http_error() ) {
			return false;
		}

		$options = PHP_INT_SIZE < 8 ? JSON_BIGINT_AS_STRING : 0; // fixes rare issue where IDs could be converted to scientific notation
		return json_decode( $this->request['body'], true, 512, $options );
	}

	/**
	 * Returns the unprocessed request body.
	 *
	 * @return string
	 */
	function get_body_raw() {
		if ( $this->is_http_error() ) {
			return '';
		}

		return $this->request['body'];
	}

	/**
	 * Checks if the HTTP status code is a success code.
	 *
	 * @return bool
	 */
	function is_http_success_code() {
		return in_array( $this->get_response_code(), $this->http_success_codes );
	}





	/**
	 * @deprecated
	 * @return bool
	 */
	function is_failed() {
		wc_deprecated_function( __METHOD__, '5.2.0', 'is_http_error' );

		return $this->is_http_error();
	}


	/**
	 * @deprecated
	 * @return bool
	 */
	function get_error_message() {
		wc_deprecated_function( __METHOD__, '5.2.0', 'get_http_error_message' );

		return $this->get_http_error_message();
	}


}
