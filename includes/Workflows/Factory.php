<?php

namespace AutomateWoo\Workflows;

use AutomateWoo\Clean;
use AutomateWoo\Entity\Workflow as WorkflowEntity;
use AutomateWoo\Exceptions\InvalidWorkflow;
use AutomateWoo\Workflow;
use WP_Error;

/**
 * @since 3.9
 */
class Factory {

	/**
	 * Get a Workflow object by its ID.
	 *
	 * @param int $id The workflow ID.
	 *
	 * @return Workflow|false The workflow object, or false when the ID is invalid or the object can't be retrieved.
	 */
	public static function get( $id ) {
		$id = (int) $id;
		if ( $id <= 0 ) {
			return false;
		}

		$id = Clean::id( $id );
		if ( ! $id ) {
			return false;
		}

		$workflow = new Workflow( $id );
		if ( ! $workflow->exists ) {
			return false;
		}

		return $workflow;
	}

	/**
	 * Create a workflow given an Entity.
	 *
	 * @since 5.1.0
	 *
	 * @param WorkflowEntity $entity The entity to use for Workflow creation.
	 *
	 * @return Workflow
	 *
	 * @throws InvalidWorkflow When the workflow already exists or there is an issue creating the workflow.
	 */
	public static function create( WorkflowEntity $entity ) {
		$workflow = self::get( $entity->get_id() );
		if ( $workflow ) {
			throw InvalidWorkflow::workflow_exists( esc_html( $workflow->get_id() ) );
		}

		return self::create_from_array( $entity->to_array() );
	}

	/**
	 * Update a workflow given an Entity.
	 *
	 * @since 6.0.10
	 *
	 * @param WorkflowEntity $entity The entity to use for Workflow update.
	 *
	 * @return Workflow
	 *
	 * @throws InvalidWorkflow When the workflow already exists or there is an issue updating the workflow.
	 */
	public static function update( WorkflowEntity $entity ) {
		return self::update_from_array( $entity->to_array() );
	}

	/**
	 * Create a workflow from an array of data.
	 *
	 * @since 5.1.0
	 *
	 * @param array $data The array of workflow data.
	 *
	 * @return Workflow
	 * @throws InvalidWorkflow When there is an issue creating the workflow.
	 */
	public static function create_from_array( $data = [] ) {
		$data = array_replace_recursive(
			[
				'title'            => '',
				'status'           => new Status( Status::DISABLED ),
				'type'             => 'automatic',
				'is_transactional' => false,
				'order'            => 0,
				'origin'           => WorkflowEntity::ORIGIN_MANUALLY_CREATED,
				'options'          => [
					'when_to_run' => 'immediately',
				],
				'trigger'          => [
					'name'    => '',
					'options' => [],
				],
				'rules'            => [],
				'actions'          => [],
			],
			$data
		);

		$post_id  = self::create_post( $data );
		$workflow = self::create_workflow_object_from_data( $post_id, $data );

		do_action( 'automatewoo/workflow/created', $workflow->get_id() );

		return $workflow;
	}

	/**
	 * Update a workflow from an array of data.
	 *
	 * @since 6.0.10
	 *
	 * @param array $data The array of workflow data.
	 *
	 * @return Workflow
	 * @throws InvalidWorkflow When there is an issue updating the workflow.
	 */
	public static function update_from_array( $data = [] ) {
		$post_id  = self::update_post( $data );
		$workflow = self::create_workflow_object_from_data( $post_id, $data );

		do_action( 'automatewoo/workflow/updated', $workflow->get_id() );

		return $workflow;
	}

	/**
	 * Create a post object from an array of workflow data.
	 *
	 * @param array $data The array of workflow data.
	 *
	 * @return int The created post ID.
	 * @throws InvalidWorkflow When there is a problem creating the post.
	 */
	private static function create_post( array $data ): int {
		$post_data = self::prepare_post_data( $data );

		$post_id = wp_insert_post( $post_data, true );
		if ( $post_id instanceof WP_Error ) {
			throw InvalidWorkflow::error_creating_workflow( esc_html( $post_id->get_error_message() ) );
		}

		return $post_id;
	}

	/**
	 * Update a post object from an array of workflow data.
	 *
	 * @param array $data The array of workflow data.
	 *
	 * @return int The updated post ID.
	 * @throws InvalidWorkflow When there is a problem updating the post.
	 */
	private static function update_post( array $data ): int {
		$post_data = self::prepare_post_data( $data );

		$post_id = wp_update_post( $post_data, true );
		if ( $post_id instanceof WP_Error ) {
			throw InvalidWorkflow::error_updating_workflow( esc_html( $post_id->get_error_message() ) );
		}

		return $post_id;
	}

	/**
	 * Create an array of post data from an array of workflow data.
	 *
	 * The 'status' property within the $data array should NOT be the post type equivalent. This
	 * method will handle converting to the post type version of the status.
	 *
	 * @param array $data The array of workflow data.
	 *
	 * @return array
	 */
	private static function prepare_post_data( $data ): array {
		if ( isset( $data['status'] ) ) {
			$data['status'] = $data['status'] instanceof Status
				? $data['status']->get_post_status()
				: ( new Status( $data['status'] ) )->get_post_status();
		}

		$post_keys = [
			'id'     => 'ID',
			'title'  => 'post_title',
			'status' => 'post_status',
			'order'  => 'menu_order',
		];

		$post_data = [ 'post_type' => Workflow::POST_TYPE ];
		foreach ( array_intersect_key( $data, $post_keys ) as $key => $value ) {
			$post_data[ $post_keys[ $key ] ] = $value;
		}

		return $post_data;
	}

	/**
	 * Create a workflow object from an array of data.
	 *
	 * @param int   $id   Workflow ID.
	 * @param array $data Workflow data.
	 *
	 * @return Workflow
	 */
	private static function create_workflow_object_from_data( int $id, array $data ): Workflow {
		$workflow = new Workflow( $id );
		$workflow->set_trigger_data( $data['trigger']['name'], $data['trigger']['options'] );
		$workflow->set_type( $data['type'] );

		if ( ! empty( $data['rules'] ) ) {
			$workflow->set_rule_data( $data['rules'] );
		}

		if ( ! empty( $data['actions'] ) ) {
			$workflow->set_actions_data( self::maybe_convert_to_legacy_action_data( $data['actions'] ) );
		}

		$workflow->update_meta( 'workflow_options', $data['options'] );
		$workflow->update_meta( 'is_transactional', $data['is_transactional'] );
		$workflow->update_meta( 'origin', $data['origin'] );

		return $workflow;
	}

	/**
	 * Convert action data structure to legacy data structure.
	 *
	 * @since 5.1.0
	 *
	 * @param array $actions
	 *
	 * @return array
	 */
	private static function maybe_convert_to_legacy_action_data( array $actions ): array {
		$converted = [];
		foreach ( $actions as $action ) {
			if ( isset( $action['action_name'] ) ) {
				$converted[] = $action;
				continue;
			}

			if ( isset( $action['name'], $action['options'] ) ) {
				$converted[] = array_merge(
					[ 'action_name' => $action['name'] ],
					$action['options']
				);
			}
		}

		return $converted;
	}
}
