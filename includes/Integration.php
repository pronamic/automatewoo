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
				. '. Endpoint: ' . $request->url
				. '. Response body: ' . print_r( $request->get_body(), true )  // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			);
		}
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
