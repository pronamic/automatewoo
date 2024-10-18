<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simpler version of \WP_Error
 *
 * @since 3.9
 */
class Error {

	/** @var string */
	public $message;

	/** @var string */
	public $code;


	/**
	 * Error constructor.
	 *
	 * @param string $message
	 * @param string $code (optional)
	 */
	public function __construct( $message, $code = '' ) {
		$this->message = $message;
		$this->code = $code;
	}


	/**
	 * @return string
	 */
	public function get_message() {
		return $this->message;
	}


	/**
	 * @return string
	 */
	public function get_code() {
		return $this->code;
	}

}
