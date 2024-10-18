<?php

namespace AutomateWoo\Rest_Api\Utilities;

use WC_REST_Exception;
use WP_Error;

/**
 * Class RestException
 *
 * @since   5.0.0
 * @package AutomateWoo\Rest_Api\Utilities
 */
class RestException extends WC_REST_Exception {

	/**
	 * Get the WP_Error equivalent of the Exception.
	 *
	 * @return WP_Error
	 */
	public function get_wp_error() {
		return new WP_Error( $this->getErrorCode(), $this->getMessage(), $this->getErrorData() );
	}
}
