<?php

namespace AutomateWoo\Rest_Api\Controllers;

use AutomateWoo\Customer;
use AutomateWoo\Log;
use AutomateWoo\Log_Query;
use AutomateWoo\Permissions;
use AutomateWoo\Rest_Api\Schema\LogSchema;
use AutomateWoo\Rest_Api\Utilities\DateHelper;
use AutomateWoo\Rest_Api\Utilities\Pagination;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Logs REST API controller.
 *
 * @since 6.0.12
 */
class LogsController extends AbstractController {

	use DateHelper;
	use LogSchema;

	/**
	 * Base route for the controller.
	 *
	 * @var string
	 */
	protected $rest_base = 'logs';

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}",
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
					'args'                => $this->get_collection_params(),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.\
	 */
	public function get_items( $request ) {
		$query = new Log_Query();
		$query->set_calc_found_rows( true );
		$query->set_limit( $request->get_param( 'per_page' ) );
		$query->set_page( $request->get_param( 'page' ) );
		$query->set_ordering( 'date', 'DESC' );

		if ( $request->has_param( 'date_after' ) ) {
			$query->where_date( $request->get_param( 'date_after' ), '>' );
		}

		if ( $request->has_param( 'date_before' ) ) {
			$query->where_date( $request->get_param( 'date_before' ), '<' );
		}

		if ( $request->has_param( 'customer' ) && [] !== $request->get_param( 'customer' ) ) {
			$query->where_customer( $request->get_param( 'customer' ) );
		}

		if ( $request->has_param( 'workflow' ) && [] !== $request->get_param( 'workflow' ) ) {
			$query->where_workflow( $request->get_param( 'workflow' ) );
		}

		if ( $request->has_param( 'shop_order' ) && [] !== $request->get_param( 'shop_order' ) ) {
			$query->where_order( $request->get_param( 'shop_order' ) );
		}

		$data  = [];
		$items = $query->get_results();
		foreach ( $items as $item ) {
			$data[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $item, $request ) );
		}

		$response = new WP_REST_Response( $data );
		$response = ( new Pagination( $request, $response, $query->found_rows ) )->add_headers();

		return $response;
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['date_before'] = [
			'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'automatewoo' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		];

		$params['date_after'] = [
			'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'automatewoo' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		];

		$params['customer'] = [
			'description'       => __( 'Limit response to one or more customers.', 'automatewoo' ),
			'type'              => 'array',
			'items'             => [
				'type' => 'string',
			],
			'default'           => [],
			'sanitize_callback' => 'wp_parse_id_list',
		];

		$params['workflow'] = [
			'description'       => __( 'Limit response to one or more workflows.', 'automatewoo' ),
			'type'              => 'array',
			'items'             => [
				'type' => 'integer',
			],
			'default'           => [],
			'sanitize_callback' => 'wp_parse_id_list',
		];

		$params['shop_order'] = [
			'description'       => __( 'Limit response to one or more orders.', 'automatewoo' ),
			'type'              => 'array',
			'items'             => [
				'type' => 'integer',
			],
			'default'           => [],
			'sanitize_callback' => 'wp_parse_id_list',
		];

		return $params;
	}

	/**
	 * Prepare the workflow for the REST response.
	 *
	 * @param Log             $log     The log object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $log, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$data = [
			'id'                             => $log->get_id(),
			'workflow'                       => [
				'id'    => $log->get_workflow_id(),
				'title' => $log->get_workflow()->get_title(),
			],
			'date'                           => $this->get_date_response_from_log( $log ),
			'has_errors'                     => $log->has_errors(),
			'has_blocked_emails'             => $log->has_blocked_emails(),
			'is_tracking_enabled'            => $log->is_tracking_enabled(),
			'is_conversion_tracking_enabled' => $log->is_conversion_tracking_enabled(),
			'is_anonymized'                  => $log->is_anonymized(),
			'has_open_recorded'              => $log->has_open_recorded(),
			'has_click_recorded'             => $log->has_click_recorded(),
			'date_opened'                    => $this->get_date_response_from_log( $log, 'opened' ),
			'date_clicked'                   => $this->get_date_response_from_log( $log, 'clicked' ),
			'notes'                          => $log->get_notes(),
		];

		$data_layer = $log->get_data_layer( 'object' );

		// Add the customer data only if the customer was found.
		$customer = $data_layer->get_customer();
		if ( $customer instanceof Customer ) {
			$data['customer'] = [
				'id'      => $customer->get_id(),
				'email'   => $customer->get_email(),
				'name'    => $customer->get_full_name(),
				'user_id' => $customer->get_user_id(),
			];
		}

		// Order data should only be added if an order is part of the log data.
		$order = $data_layer->get_order();
		if ( $order instanceof WC_Order ) {
			$data['order_id'] = $order->get_id();
		}

		return rest_ensure_response( $data );
	}
}
