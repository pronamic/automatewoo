<?php


namespace AutomateWoo\Rest_Api\Controllers;

use AutomateWoo\Rest_Api\Utilities\Controller_Namespace;
use Exception;
use WP_Error;
use WP_REST_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract AutomateWoo REST controller.
 *
 * @since 4.9.0
 */
abstract class AbstractController extends WP_REST_Controller {

	use Controller_Namespace;

	/**
	 * Get rest error from an exception.
	 *
	 * @since 5.0.0
	 *
	 * @param Exception $exception
	 *
	 * @return WP_Error
	 */
	protected function get_rest_error_from_exception( Exception $exception ) {
		return new WP_Error( 'rest_error', $exception->getMessage() ?: __( 'Unknown error.', 'automatewoo' ) );
	}
}
