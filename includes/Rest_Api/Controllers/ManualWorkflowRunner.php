<?php

namespace AutomateWoo\Rest_Api\Controllers;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\DateTime;
use AutomateWoo\Permissions;
use AutomateWoo\Queued_Event;
use AutomateWoo\Rest_Api\Utilities\GetWorkflow;
use AutomateWoo\Rest_Api\Utilities\RestException;
use AutomateWoo\RuleQuickFilters\QueryLoader;
use AutomateWoo\Triggers\ManualInterface;
use AutomateWoo\Workflow;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * ManualWorkflowRunner Rest API controller.
 *
 * @since 5.0.0
 */
class ManualWorkflowRunner extends AbstractController {

	use GetWorkflow;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'manual-workflow-runner';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		$workflow_id_arg = [
			'description' => __( 'The workflow ID.', 'automatewoo' ),
			'type'        => 'integer',
		];

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/quick-filter-data/(?P<workflow>[\d]+)",
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_quick_filter_data' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
					'args'                => [
						'workflow' => $workflow_id_arg,
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/find-matches/(?P<workflow>[\d]+)",
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'find_matches' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
					'args'                => [
						'workflow'   => $workflow_id_arg,
						'rule_group' => [
							'description'       => __( 'The rule group number to find matches for.', 'automatewoo' ),
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'rest_sanitize_request_arg',
						],
						'offset'     => [
							'description'       => __( 'The current batch offset relative to the rule group number.', 'automatewoo' ),
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'rest_sanitize_request_arg',
						],
						'batch_size' => [
							'description'       => __( 'The maximum number of items to find in a single request.', 'automatewoo' ),
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'rest_sanitize_request_arg',
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/add-items-to-queue/(?P<workflow>[\d]+)",
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_items_to_queue' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
					'args'                => [
						'workflow' => $workflow_id_arg,
						'batch'    => [
							'description'       => __( 'A batch of items to add to the workflow queue.', 'automatewoo' ),
							'type'              => 'array',
							'required'          => true,
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'rest_sanitize_request_arg',
							'items'             => [
								'type' => 'integer',
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Retrieves quick filter data for a workflow.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_quick_filter_data( $request ) {
		try {
			$workflow = $this->get_workflow( $request->get_param( 'workflow' ) );
			$this->validate_manual_workflow( $workflow );

			$data_type          = $workflow->get_trigger()->get_primary_data_type();
			$quick_filter_query = QueryLoader::load( $workflow->get_rule_data(), $data_type );
			$result_counts      = [];

			foreach ( $quick_filter_query->get_results_counts_for_each_rule_group() as $group_number => $count ) {
				$result_counts[] = [
					'group_number' => $group_number,
					'count'        => $count,
				];
			}

			return rest_ensure_response(
				[
					'primaryDataType'      => $data_type,
					'possibleResultCounts' => $result_counts,
				]
			);
		} catch ( RestException $e ) {
			return $e->get_wp_error();
		} catch ( \Exception $e ) {
			return $this->get_rest_error_from_exception( $e );
		}
	}

	/**
	 * Find batch of quick filter matches for a workflow.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function find_matches( $request ) {
		try {
			$workflow = $this->get_workflow( $request->get_param( 'workflow' ) );
			$this->validate_manual_workflow( $workflow );

			$trigger    = $workflow->get_trigger();
			$rule_group = $request->get_param( 'rule_group' );
			$offset     = $request->get_param( 'offset' );
			$batch_size = $request->get_param( 'batch_size' );

			$data_type               = DataTypes::get( $trigger->get_primary_data_type() );
			$data_type_singular_name = $data_type->get_singular_name();
			$quick_filter_query      = QueryLoader::load( $workflow->get_rule_data(), $data_type->get_id() );
			$results                 = [];

			foreach ( $quick_filter_query->get_results_by_rule_group( $rule_group, $batch_size, $offset ) as $item ) {
				// do real rule validation
				$workflow->setup( $trigger->get_data_layer( $item ) );

				if ( $workflow->validate_workflow() ) {
					if ( $item instanceof WC_Order ) {
						$url = $item->get_edit_order_url();
					} else {
						$url = get_edit_post_link( $item->get_id(), 'raw' );
					}

					$results[] = [
						'id'           => $item->get_id(),
						'singularName' => $data_type_singular_name,
						'url'          => $url,
					];
				}

				$workflow->cleanup();
			}

			return rest_ensure_response( $results );
		} catch ( RestException $e ) {
			return $e->get_wp_error();
		} catch ( \Exception $e ) {
			return $this->get_rest_error_from_exception( $e );
		}
	}

	/**
	 * Adds a batch of items to the queue.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function add_items_to_queue( $request ) {
		try {
			$workflow = $this->get_workflow( $request->get_param( 'workflow' ) );
			$this->validate_manual_workflow( $workflow );

			$items   = $request->get_param( 'batch' );
			$trigger = $workflow->get_trigger();

			foreach ( $items as $item_id ) {
				// Set data layer and validate workflow for item
				$data_layer = $trigger->get_data_layer( $item_id );
				$workflow->setup( $data_layer );
				if ( ! $workflow->validate_workflow() ) {
					continue;
				}

				// Queue item to run now
				$queue = new Queued_Event();
				$queue->set_workflow_id( $workflow->get_id() );

				if ( 'immediately' === $workflow->get_timing_type() ) {
					// If workflow should run immediately queue for now
					$queue->set_date_due( new DateTime() );
				} else {
					// Or use queue date set in workflow
					$queue->set_date_due( $workflow->get_queue_date() );
				}

				$queue->save();

				// If no data layer is set an error will be shown in the queue when the workflow tries to run
				if ( $data_layer ) {
					$queue->store_data_layer( $data_layer );
				}
			}

			return rest_ensure_response( [] );
		} catch ( RestException $e ) {
			return $e->get_wp_error();
		} catch ( \Exception $e ) {
			return $this->get_rest_error_from_exception( $e );
		}
	}

	/**
	 * Check the workflow is a valid manual workflow.
	 *
	 * @param Workflow $workflow
	 *
	 * @throws RestException If not valid.
	 */
	protected function validate_manual_workflow( $workflow ) {
		if ( 'manual' !== $workflow->get_type() || ! $workflow->get_trigger() instanceof ManualInterface ) {
			throw new RestException(
				'rest_not_manual_workflow',
				esc_html__( 'Workflow must be a manual workflow.', 'automatewoo' )
			);
		}
	}
}
