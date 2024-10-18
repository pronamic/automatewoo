<?php

namespace AutomateWoo\Rest_Api\Controllers;

use AutomateWoo\Permissions;
use AutomateWoo\Workflows\Presets\PresetInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Presets Rest API controller.
 *
 * @since 5.1.0
 */
class WorkflowPresets extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'presets';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/",
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/create-workflow",
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_workflow' ],
					'permission_callback' => [ Permissions::class, 'can_manage' ],
				],
				'args' => [
					'preset_name' => [
						'description'       => __( 'The name of the preset.', 'automatewoo' ),
						'type'              => 'string',
						'validate_callback' => 'rest_validate_request_arg',
					],
				],
			]
		);
	}

	/**
	 * Retrieves workflow presets and guides.
	 *
	 * This endpoint returns "workflow presets" and "workflow guides" where guides are just links to docs.
	 * Eventually these guides will be converted to full presets.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$array_presets = AW()->preset_service()->get_presets();
		$presets       = [];
		$guides        = [];

		foreach ( $array_presets as $array_preset ) {

			$preset = $this->prepare_item_for_response( $array_preset, $request );
			$preset = $this->prepare_response_for_collection( $preset );

			if ( PresetInterface::PRESET_TYPE_GUIDE === $array_preset['type'] ) {
				$guides[] = $preset;
			} else {
				$presets[] = $preset;
			}
		}

		if ( empty( $presets ) && empty( $guides ) ) {
			return new WP_Error( 'aw_preset_missing', __( 'Unable to load preset workflows. Please check the logs for more info.', 'automatewoo' ) );
		}

		return rest_ensure_response( array_merge( $presets, $guides ) );
	}

	/**
	 * Handle creating a workflow from a preset.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_workflow( $request ) {
		$workflow = AW()->preset_service()->save_as_workflow_by_id( $request->get_param( 'preset_name' ) );

		if ( is_wp_error( $workflow ) ) {
			return $workflow;
		}

		return rest_ensure_response(
			[
				'workflow_id' => $workflow->id,
			]
		);
	}
	/**
	 * Prepares the item for the REST response.
	 *
	 * @param PresetInterface  $preset  Preset object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $preset, $request ) {
		$fields = $this->get_fields_for_response( $request );
		$data   = [];

		if ( in_array( 'name', $fields, true ) ) {
			$data['name'] = $preset->get( 'name' );
		}

		if ( in_array( 'type', $fields, true ) ) {
			$data['type'] = $preset->get( 'type' );

			if ( PresetInterface::PRESET_TYPE_GUIDE === $data['type'] && in_array( 'link', $fields, true ) ) {
				$data['link'] = $preset->get( 'link' );
			}
		}

		if ( in_array( 'title', $fields, true ) ) {
			$data['title'] = $preset->get( 'title' );
		}

		if ( in_array( 'description', $fields, true ) ) {
			$data['description'] = $preset->get( 'description' );
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		return rest_ensure_response( $data );
	}

	/**
	 * Get the Preset schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'preset',
			'type'       => 'object',
			'properties' => [
				'name'        => [
					'description' => __( 'Unique name for the preset.', 'automatewoo' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'type'        => [
					'description' => __( 'A string defining the type of the preset workflow.', 'automatewoo' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'title'       => [
					'description' => __( 'A short title indicating the purpose of this preset workflow.', 'automatewoo' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'description' => [
					'description' => __( 'A short description about this preset.', 'automatewoo' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'link'        => [
					'description' => __( 'Link to the documentation page for the preset.', 'automatewoo' ),
					'type'        => 'string',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
			],
		];

		return $this->add_additional_fields_schema( $schema );
	}
}
