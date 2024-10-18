<?php

namespace AutomateWoo\Rest_Api\Controllers;

use AutomateWoo\Format;
use AutomateWoo\Permissions;
use AutomateWoo\Rest_Api\Schema\WorkflowSchema;
use AutomateWoo\Rest_Api\Utilities\CreateUpdateWorkflow;
use AutomateWoo\Rest_Api\Utilities\DeleteWorkflow;
use AutomateWoo\Rest_Api\Utilities\GetWorkflow;
use AutomateWoo\Rest_Api\Utilities\Pagination;
use AutomateWoo\Rest_Api\Utilities\RestException;
use AutomateWoo\Workflow;
use AutomateWoo\Workflows as WorkflowsHelper;
use AutomateWoo\Workflow_Query;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Workflows Rest API controller.
 *
 * @since 4.9.0
 */
class Workflows extends AbstractController {

	use WorkflowSchema;
	use CreateUpdateWorkflow;
	use DeleteWorkflow;
	use GetWorkflow;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'workflows';

	/**
	 * Register the routes.
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
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
					'args'                => $this->get_properties_schema(),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/(?P<id>[\d]+)",
			[
				'args'   => [
					'id' => [
						'description' => __( 'Unique identifier for the workflow.', 'automatewoo' ),
						'type'        => 'integer',
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
					'args'                => $this->get_update_parameters_schema(),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
					'args'                => $this->get_delete_parameters_schema(),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Retrieves a collection workflows.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$query = new Workflow_Query();
		$query->set_no_found_rows( false );
		$query->set_limit( $request->get_param( 'per_page' ) );
		$query->set_page( $request->get_param( 'page' ) );
		$query->set_status( $request->get_param( 'status' ) ?: 'any' );
		$query->set_trigger( $request->get_param( 'trigger' ) );

		if ( $request->has_param( 'type' ) ) {
			$query->set_type( $request->get_param( 'type' ) );
		}

		if ( $request->has_param( 'search' ) ) {
			$query->set_search( $request->get_param( 'search' ) );
		}
		if ( $request->has_param( 'include' ) ) {
			$query->set_include( $request->get_param( 'include' ) );
		}

		$items = $query->get_results();
		$data  = [];

		foreach ( $items as $item ) {
			$item_data = $this->prepare_item_for_response( $item, $request );
			$data[]    = $this->prepare_response_for_collection( $item_data );
		}

		$response = new WP_REST_Response( $data );
		$response = ( new Pagination( $request, $response, $query->get_found_rows() ) )->add_headers();

		return $response;
	}

	/**
	 * Retrieves a single Workflow item.
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		try {
			$workflow = $this->get_workflow( $request['id'] );
		} catch ( RestException $e ) {
			return $e->get_wp_error();
		}

		return rest_ensure_response( $this->prepare_item_for_response( $workflow, $request ) );
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['status'] = [
			'description'       => __( 'Limit results by status.', 'automatewoo' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
			'enum'              => [ 'active', 'disabled' ],
		];

		$params['type'] = [
			'description'       => __( 'Limit results by type.', 'automatewoo' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
			'enum'              => array_keys( WorkflowsHelper::get_types() ),
		];

		$params['trigger'] = [
			'description'       => __( 'Limit results by one or more triggers.', 'automatewoo' ),
			'type'              => 'array',
			'items'             => [
				'type' => 'string',
			],
			'default'           => [],
			'sanitize_callback' => 'wp_parse_slug_list',
		];

		return $params;
	}

	/**
	 * Creates a single Workflow item.
	 *
	 * @since 6.0.10
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		try {
			$workflow = $this->create_workflow( $request );
		} catch ( RestException $e ) {
			return $e->get_wp_error();
		}

		return rest_ensure_response( $this->prepare_item_for_response( $workflow, $request ) );
	}

	/**
	 * Updates a single Workflow item.
	 *
	 * @since 6.0.10
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		try {
			$workflow = $this->update_workflow( $request );
		} catch ( RestException $e ) {
			return $e->get_wp_error();
		}

		return rest_ensure_response( $this->prepare_item_for_response( $workflow, $request ) );
	}

	/**
	 * Deletes a single Workflow item (moves to trash if available).
	 *
	 * @since 6.0.10
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		try {
			$id    = (int) $request['id'];
			$force = boolval( $request['force'] );

			$workflow = $this->delete_workflow( $id, $force );
		} catch ( RestException $e ) {
			return $e->get_wp_error();
		}

		// If force deleted return a simple response as the full workflow data is no longer available.
		if ( 'deleted' === $workflow->get_status() ) {
			return rest_ensure_response(
				[
					'id'     => $workflow->get_id(),
					'status' => $workflow->get_status(),
				]
			);
		}

		return rest_ensure_response( $this->prepare_item_for_response( $workflow, $request ) );
	}

	/**
	 * Prepare the workflow for the REST response.
	 *
	 * @param Workflow        $workflow
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|mixed
	 */
	public function prepare_item_for_response( $workflow, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$data = [
			'id'                             => $workflow->get_id(),
			'title'                          => $workflow->get_title(),
			'status'                         => $workflow->get_status(),
			'type'                           => $workflow->get_type(),
			'trigger'                        => [
				'name'    => $workflow->get_trigger_name(),
				'options' => $workflow->get_trigger_options(),
			],
			'rules'                          => $workflow->get_rule_data(),
			'actions'                        => array_values( $workflow->get_actions_data() ),
			'timing'                         => $this->prepare_timing_for_response( $workflow ),
			'is_transactional'               => $workflow->is_transactional(),
			'is_tracking_enabled'            => $workflow->is_tracking_enabled(),
			'is_conversion_tracking_enabled' => $workflow->is_conversion_tracking_enabled(),
			'google_analytics_link_tracking' => $workflow->get_ga_tracking_params(),
			'workflow_order'                 => $workflow->get_order(),
		];

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare the workflow timing for the REST response.
	 *
	 * @param Workflow $workflow
	 *
	 * @return array
	 */
	protected function prepare_timing_for_response( $workflow ) {
		$data = [
			'type' => $workflow->get_timing_type(),
		];

		switch ( $workflow->get_timing_type() ) {
			case 'delayed':
				$data['delay'] = [
					'unit'  => $workflow->get_timing_delay_unit(),
					'value' => $workflow->get_timing_delay_number(),
				];
				break;
			case 'scheduled':
				$data['scheduled'] = [
					'time_of_day' => $workflow->get_scheduled_time(),
					'days'        => array_map( Format::class . '::api_weekday', $workflow->get_scheduled_days() ),
				];

				if ( $workflow->get_timing_delay_number() ) {
					$data['delay'] = [
						'unit'  => $workflow->get_timing_delay_unit(),
						'value' => $workflow->get_timing_delay_number(),
					];
				}
				break;
			case 'fixed':
				$data['datetime'] = Format::api_datetime( $workflow->get_fixed_time() );
				break;
			case 'datetime':
				$data['variable'] = $workflow->get_option( 'queue_datetime', false );
				break;
		}

		return $data;
	}
}
