<?php

namespace AutomateWoo;

use AutomateWoo\Rest_Api\Controllers\ConversionsController;
use AutomateWoo\Rest_Api\Controllers\LogsController;
use AutomateWoo\Rest_Api\Controllers\WorkflowPresets as WorkflowPresetsController;
use AutomateWoo\Rest_Api\Controllers\Workflows as WorkflowsController;
use AutomateWoo\Rest_Api\Controllers\ManualWorkflowRunner as ManualWorkflowRunnerController;
use AutomateWoo\Rest_Api\Utilities\Controller_Namespace;
use AutomateWoo\Admin\Analytics\Rest_API as Analytics_Rest_API;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Rest_Api
 *
 * @since 4.9.0
 */
final class Rest_Api {

	use Controller_Namespace;

	/**
	 * Init AutomateWoo's Rest API.
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ], 15 );
		add_filter( 'rest_namespace_index', [ $this, 'filter_namespace_index' ], 10, 2 );
		add_filter( 'woocommerce_rest_is_request_to_rest_api', [ $this, 'allow_wc_rest_api_authentication' ] );
		Analytics_Rest_API::init();
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		foreach ( $this->get_controllers() as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * Get REST API controller objects.
	 *
	 * @return WP_REST_Controller[]
	 * @throws Exception When a class doesn't implement the correct interface.
	 */
	private function get_controllers() {
		$classes = [
			LogsController::class,
			WorkflowsController::class,
			ManualWorkflowRunnerController::class,
			WorkflowPresetsController::class,
			ConversionsController::class,
		];

		$controllers = [];
		foreach ( $classes as $class ) {
			$object = new $class();
			if ( ! $object instanceof WP_REST_Controller ) {
				throw new Exception(
					esc_html( sprintf( '%s must implement %s', get_class( $object ), WP_REST_Controller::class ) )
				);
			}

			$controllers[] = $object;
		}

		return $controllers;
	}

	/**
	 * Filter the index response for our namespace.
	 *
	 * @param WP_REST_Response $response The response object for the given request.
	 * @param WP_REST_Request  $request  The current REST Request object.
	 *
	 * @return WP_REST_Response The filtered response object.
	 */
	public function filter_namespace_index( $response, $request ) {
		if ( $this->namespace === $request['namespace'] ) {
			$response->data['info'] = __( 'AutomateWoo API endpoints are still under development and subject to change.', 'automatewoo' );
		}

		return $response;
	}

	/**
	 * Allow the AutomateWoo namespace to use WC REST API authentication.
	 *
	 * @since 6.0.10
	 *
	 * @param boolean $allow Allow the request to use WC REST API authentication.
	 *
	 * @return boolean
	 */
	public function allow_wc_rest_api_authentication( bool $allow ): bool {
		if ( $allow || empty( $_SERVER['REQUEST_URI'] ) ) {
			return $allow;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		// Check if the request is to the AutomateWoo namespace.
		if ( false !== strpos( $request_uri, "{$rest_prefix}{$this->namespace}/" ) ) {
			return true;
		}

		return $allow;
	}
}
