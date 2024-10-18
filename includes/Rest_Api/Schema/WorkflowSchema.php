<?php

namespace AutomateWoo\Rest_Api\Schema;

use AutomateWoo\Workflows;

/**
 * Schema for a Workflow object in the REST API.
 *
 * @since   4.9.0
 *
 * @package AutomateWoo\Rest_Api\Schema
 */
trait WorkflowSchema {

	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = [
			'$schema'    => 'https://json-schema.org/draft-04/schema#',
			'title'      => 'workflow',
			'type'       => 'object',
			'properties' => $this->get_properties_schema(),
		];

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Retrieves the schema for the item properties.
	 *
	 * @return array Item properties schema data.
	 */
	protected function get_properties_schema() {
		return [
			'id'                             => [
				'description' => __( 'Unique identifier for the object.', 'automatewoo' ),
				'type'        => 'integer',
				'context'     => Context::VIEW_ONLY,
				'readonly'    => true,
			],
			'title'                          => [
				'description' => __( 'Workflow name', 'automatewoo' ),
				'type'        => 'string',
				'context'     => Context::ALL,
				'required'    => true,
			],
			'status'                         => [
				'description' => __( 'Whether the Workflow is active or disabled.', 'automatewoo' ),
				'type'        => 'string',
				'enum'        => [ 'disabled', 'active' ],
				'context'     => Context::ALL,
			],
			'type'                           => [
				'description' => __( 'The workflow type.', 'automatewoo' ),
				'type'        => 'string',
				'enum'        => array_keys( Workflows::get_types() ),
				'context'     => Context::ALL,
			],
			'trigger'                        => [
				'description' => __( 'The type of event that triggers the workflow to run.', 'automatewoo' ),
				'type'        => 'object',
				'context'     => Context::ALL,
				'required'    => true,
				'properties'  => [
					'name'    => [
						'description' => __( 'The name of the trigger for the workflow.', 'automatewoo' ),
						'type'        => 'string',
						'required'    => true,
					],
					'options' => [
						'description' => __( 'The options for the workflow trigger.', 'automatewoo' ),
						'type'        => 'object',
					],
				],
			],
			'rules'                          => [
				'description' => __( 'Collection of rules to add conditional logic to workflow.', 'automatewoo' ),
				'type'        => 'array',
				'context'     => Context::ALL,
				'items'       => [
					'description' => __( 'Collection of rule groups.', 'automatewoo' ),
					'type'        => 'array',
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'name'    => [
								'description' => __( 'Rule name.', 'automatewoo' ),
								'type'        => 'string',
								'required'    => true,
							],
							'compare' => [
								'description' => __( 'Rule comparison operator.', 'automatewoo' ),
								'type'        => 'string',
							],
							'value'   => [
								'description' => __( 'Rule value to compare.', 'automatewoo' ),
								'type'        => [ 'string', 'array', 'object' ],
							],
						],
					],
				],
			],
			'actions'                        => [
				'description' => __( 'Collection of actions to run for workflow.', 'automatewoo' ),
				'type'        => 'array',
				'context'     => Context::ALL,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'action_name' => [
							'description' => __( 'Name of the action.', 'automatewoo' ),
							'type'        => 'string',
							'required'    => true,
						],
					],
				],
			],
			'timing'                         => [
				'description' => __( 'When the workflow will run in relation to the trigger.', 'automatewoo' ),
				'type'        => 'object',
				'context'     => Context::ALL,
				'properties'  => [
					'type'      => [
						'description' => __( 'Timing type for when the workflow will run in relation to the trigger.', 'automatewoo' ),
						'type'        => 'string',
						'enum'        => [ 'immediately', 'delayed', 'scheduled', 'fixed', 'datetime' ],
						'required'    => true,
					],
					'delay'     => [
						'description' => __( 'Amount of time to delay before running the workflow.', 'automatewoo' ),
						'type'        => 'object',
						'properties'  => [
							'unit'  => [
								'description' => __( 'Unit of time to delay (hour, minute, day, week, month).', 'automatewoo' ),
								'type'        => 'string',
								'enum'        => [ 'h', 'm', 'd', 'w', 'month' ],
							],
							'value' => [
								'description' => __( 'Number of time to wait.', 'automatewoo' ),
								'type'        => 'number',
							],
						],
					],
					'scheduled' => [
						'description' => __( 'Scheduled time of day / days to run the workflow.', 'automatewoo' ),
						'type'        => 'object',
						'properties'  => [
							'time_of_day' => [
								'description' => __( 'Time of day to run the workflow (HH:mm).', 'automatewoo' ),
								'type'        => 'string',
							],
							'days'        => [
								'description' => __( 'Days of the week to run the workflow (empty is any day).', 'automatewoo' ),
								'type'        => 'array',
								'items'       => [
									'type' => 'string',
									'enum' => [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ],
								],
							],
						],
					],
					'datetime'  => [
						'description' => __( 'Fixed time to run the workflow.', 'automatewoo' ),
						'type'        => 'string',
						'format'      => 'date-time',
					],
					'variable'  => [
						'description' => __( 'Variable to use as scheduled time to run the workflow.', 'automatewoo' ),
						'type'        => 'string',
					],
				],
			],
			'is_transactional'               => [
				'description' => __( 'Whether the workflow is used for transactional emails instead of marketing emails.', 'automatewoo' ),
				'type'        => 'boolean',
				'context'     => Context::ALL,
			],
			'is_tracking_enabled'            => [
				'description' => __( 'Whether tracking is enabled for the workflow.', 'automatewoo' ),
				'type'        => 'boolean',
				'context'     => Context::ALL,
			],
			'is_conversion_tracking_enabled' => [
				'description' => __( 'Whether conversion tracking is enabled for the workflow.', 'automatewoo' ),
				'type'        => 'boolean',
				'context'     => Context::ALL,
			],
			'google_analytics_link_tracking' => [
				'description' => __( 'Tracking variables to be appended to every link in the email or SMS.', 'automatewoo' ),
				'type'        => 'string',
				'context'     => Context::ALL,
			],
			'workflow_order'                 => [
				'description' => __( 'The order in which the workflows will run.', 'automatewoo' ),
				'type'        => 'integer',
				'context'     => Context::ALL,
			],
		];
	}

	/**
	 * Retrieves the schema for update parameters.
	 *
	 * @return array Update parameters schema data.
	 */
	protected function get_update_parameters_schema() {
		return array_map(
			function ( $parameter ) {
				unset( $parameter['required'] );
				return $parameter;
			},
			$this->get_properties_schema()
		);
	}

	/**
	 * Retrieves the schema for delete parameters.
	 *
	 * @return array Delete parameters schema data.
	 */
	protected function get_delete_parameters_schema() {
		return [
			'force' => [
				'description' => __( 'Use true to permanently delete the workflow, default is false.', 'automatewoo' ),
				'type'        => 'boolean',
			],
		];
	}

	/**
	 * Adds the schema from additional fields to a schema array.
	 *
	 * The type of object is inferred from the passed schema.
	 *
	 * @param array $schema Schema array.
	 *
	 * @return array Modified Schema array.
	 */
	abstract protected function add_additional_fields_schema( $schema );
}
