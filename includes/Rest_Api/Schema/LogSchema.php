<?php

namespace AutomateWoo\Rest_Api\Schema;

defined( 'ABSPATH' ) || exit;

trait LogSchema {

	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = [
			'$schema'    => 'https://json-schema.org/draft-04/schema#',
			'title'      => 'logs',
			'type'       => 'object',
			'properties' => [
				'id'                             => [
					'description' => __( 'Unique identifier for the object.', 'automatewoo' ),
					'type'        => 'integer',
					'context'     => Context::VIEW_ONLY,
					'readonly'    => true,
				],
				'workflow'                       => [
					'description' => __( 'Workflow data for the log.', 'automatewoo' ),
					'type'        => 'object',
					'context'     => Context::VIEW_ONLY,
					'properties'  => [
						'id'    => [
							'description' => __( 'The Workflow ID.', 'automatewoo' ),
							'type'        => 'integer',
							'context'     => Context::VIEW_ONLY,
							'readonly'    => true,
						],
						'title' => [
							'description' => __( 'The title of the workflow.', 'automatewoo' ),
							'type'        => 'string',
							'context'     => Context::VIEW_ONLY,
						],
					],
				],
				'customer'                       => [
					'description' => __( 'Customer data for the log.', 'automatewoo' ),
					'type'        => 'object',
					'context'     => Context::VIEW_ONLY,
					'properties'  => [
						'id'      => [
							'description' => __( 'The Customer ID within AutomateWoo.', 'automatewoo' ),
							'type'        => 'integer',
							'context'     => Context::VIEW_ONLY,
							'readonly'    => true,
						],
						'email'   => [
							'description' => __( 'The customer email address.', 'automatewoo' ),
							'type'        => 'string',
							'context'     => Context::VIEW_ONLY,
						],
						'name'    => [
							'description' => __( "The customer's full name.", 'automatewoo' ),
							'type'        => 'string',
							'context'     => Context::VIEW_ONLY,
						],
						'user_id' => [
							'description' => __( 'The WordPress User ID for the customer.', 'automatewoo' ),
							'type'        => 'integer',
							'context'     => Context::VIEW_ONLY,
							'readonly'    => true,
						],
					],
				],
				'date'                           => [
					'description' => __( 'The date of the log.', 'automatewoo' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => Context::VIEW_ONLY,
				],
				'has_errors'                     => [
					'description' => __( 'Whether the workflow generated errors.', 'automatewoo' ),
					'type'        => 'boolean',
					'context'     => Context::VIEW_ONLY,
				],
				'has_blocked_emails'             => [
					'description' => __( 'Whether the workflow had blocked emails.', 'automatewoo' ),
					'type'        => 'boolean',
					'context'     => Context::VIEW_ONLY,
				],
				'is_tracking_enabled'            => [
					'description' => __( 'Whether the workflow has tracking enabled.', 'automatewoo' ),
					'type'        => 'boolean',
					'context'     => Context::VIEW_ONLY,
				],
				'is_conversion_tracking_enabled' => [
					'description' => __( 'Whether the workflow has conversion tracking enabled,', 'automatewoo' ),
					'type'        => 'boolean',
					'context'     => Context::VIEW_ONLY,
				],
				'is_anonymized'                  => [
					'description' => __( 'Whether the user data is anonymized.', 'automatewoo' ),
					'type'        => 'boolean',
					'context'     => Context::VIEW_ONLY,
				],
				'has_open_recorded'              => [
					'description' => __( 'Whether an open has been recorded.', 'automatewoo' ),
					'type'        => 'boolean',
					'context'     => Context::VIEW_ONLY,
				],
				'has_click_recorded'             => [
					'description' => __( 'Whether a click has been recorded.', 'automatewoo' ),
					'type'        => 'boolean',
					'context'     => Context::VIEW_ONLY,
				],
				'date_opened'                    => [
					'description' => __( 'The date the open was recorded.', 'automatewoo' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => Context::VIEW_ONLY,
				],
				'date_clicked'                   => [
					'description' => __( 'The date the click was recorded.', 'automatewoo' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => Context::VIEW_ONLY,
				],
				'notes'                          => [
					'description' => __( 'Notes for the log.', 'automatewoo' ),
					'type'        => 'array',
					'items'       => [
						'type' => 'string',
					],
					'context'     => Context::VIEW_ONLY,
				],
				'order_id'                       => [
					'description' => __( 'The order ID for the log.', 'automatewoo' ),
					'type'        => 'integer',
					'context'     => Context::VIEW_ONLY,
					'readonly'    => true,
				],
			],
		];

		return $this->add_additional_fields_schema( $schema );
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
