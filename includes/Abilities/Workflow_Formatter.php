<?php
/**
 * AutomateWoo workflow ability formatter.
 *
 * @package AutomateWoo\Abilities
 */

namespace AutomateWoo\Abilities;

use AutomateWoo\DateTime;
use AutomateWoo\Format;
use AutomateWoo\Workflow;

defined( 'ABSPATH' ) || exit;

/**
 * Formats AutomateWoo workflow data for ability responses.
 */
class Workflow_Formatter {

	/**
	 * Formats a workflow without exposing rule values, action payloads, or customer data.
	 *
	 * @param Workflow $workflow Workflow object.
	 * @return array
	 */
	public static function format_workflow( Workflow $workflow ): array {
		$actions = array_values(
			array_filter(
				array_map(
					static function ( $action ): string {
						return is_array( $action ) && isset( $action['action_name'] ) ? sanitize_key( $action['action_name'] ) : '';
					},
					$workflow->get_actions_data()
				)
			)
		);

		return [
			'id'                             => $workflow->get_id(),
			'title'                          => $workflow->get_title(),
			'status'                         => $workflow->get_status(),
			'type'                           => $workflow->get_type(),
			'trigger_name'                   => $workflow->get_trigger_name(),
			'action_names'                   => $actions,
			'action_count'                   => count( $actions ),
			'rule_group_count'               => $workflow->get_rule_group_count(),
			'timing'                         => self::format_timing( $workflow ),
			'is_transactional'               => $workflow->is_transactional(),
			'is_tracking_enabled'            => $workflow->is_tracking_enabled(),
			'is_conversion_tracking_enabled' => $workflow->is_conversion_tracking_enabled(),
			'workflow_order'                 => $workflow->get_order(),
			'date_created_gmt'               => self::format_datetime( $workflow->get_date_created() ),
		];
	}

	/**
	 * Gets read-only ability metadata.
	 *
	 * @return array
	 */
	public static function get_readonly_meta(): array {
		return [
			'show_in_rest' => true,
			'mcp'          => [
				'public' => true,
				'type'   => 'tool',
			],
			'annotations'  => [
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			],
		];
	}

	/**
	 * Gets the workflow output schema.
	 *
	 * @return array
	 */
	public static function get_workflow_schema(): array {
		return [
			'type'                 => 'object',
			'properties'           => [
				'id'                             => [
					'type' => 'integer',
				],
				'title'                          => [
					'type' => 'string',
				],
				'status'                         => [
					'type' => 'string',
				],
				'type'                           => [
					'type' => 'string',
				],
				'trigger_name'                   => [
					'type' => 'string',
				],
				'action_names'                   => [
					'type'  => 'array',
					'items' => [
						'type' => 'string',
					],
				],
				'action_count'                   => [
					'type' => 'integer',
				],
				'rule_group_count'               => [
					'type' => 'integer',
				],
				'timing'                         => self::get_timing_schema(),
				'is_transactional'               => [
					'type' => 'boolean',
				],
				'is_tracking_enabled'            => [
					'type' => 'boolean',
				],
				'is_conversion_tracking_enabled' => [
					'type' => 'boolean',
				],
				'workflow_order'                 => [
					'type' => 'integer',
				],
				'date_created_gmt'               => [
					'type'   => 'string',
					'format' => 'date-time',
				],
			],
			'required'             => [
				'id',
				'title',
				'status',
				'type',
				'trigger_name',
				'action_names',
				'action_count',
				'rule_group_count',
				'timing',
				'is_transactional',
				'is_tracking_enabled',
				'is_conversion_tracking_enabled',
				'workflow_order',
				'date_created_gmt',
			],
			'additionalProperties' => false,
		];
	}

	/**
	 * Formats workflow timing data.
	 *
	 * @param Workflow $workflow Workflow object.
	 * @return array
	 */
	private static function format_timing( Workflow $workflow ): array {
		$timing = [
			'type' => $workflow->get_timing_type(),
		];

		switch ( $workflow->get_timing_type() ) {
			case 'delayed':
				$timing['delay'] = [
					'unit'  => $workflow->get_timing_delay_unit(),
					'value' => $workflow->get_timing_delay_number(),
				];
				break;
			case 'scheduled':
				$timing['scheduled'] = [
					'time_of_day' => $workflow->get_scheduled_time(),
					'days'        => array_map( Format::class . '::api_weekday', $workflow->get_scheduled_days() ),
				];
				if ( $workflow->get_timing_delay_number() ) {
					$timing['delay'] = [
						'unit'  => $workflow->get_timing_delay_unit(),
						'value' => $workflow->get_timing_delay_number(),
					];
				}
				break;
			case 'fixed':
				$fixed_time = $workflow->get_fixed_time();
				if ( $fixed_time ) {
					$timing['datetime'] = self::format_datetime( $fixed_time );
				}
				break;
			case 'datetime':
				$timing['variable'] = (string) $workflow->get_option( 'queue_datetime', false );
				break;
		}

		return $timing;
	}

	/**
	 * Gets the workflow timing schema.
	 *
	 * @return array
	 */
	private static function get_timing_schema(): array {
		return [
			'type'                 => 'object',
			'properties'           => [
				'type'      => [
					'type' => 'string',
					'enum' => [ 'immediately', 'delayed', 'scheduled', 'fixed', 'datetime' ],
				],
				'delay'     => [
					'type'                 => 'object',
					'properties'           => [
						'unit'  => [
							'type' => 'string',
						],
						'value' => [
							'type' => 'number',
						],
					],
					'additionalProperties' => false,
				],
				'scheduled' => [
					'type'                 => 'object',
					'properties'           => [
						'time_of_day' => [
							'type' => 'string',
						],
						'days'        => [
							'type'  => 'array',
							'items' => [
								'type' => 'string',
							],
						],
					],
					'additionalProperties' => false,
				],
				'datetime'  => [
					'type'   => 'string',
					'format' => 'date-time',
				],
				'variable'  => [
					'type' => 'string',
				],
			],
			'required'             => [ 'type' ],
			'additionalProperties' => false,
		];
	}

	/**
	 * Formats AutomateWoo date values for ability output.
	 *
	 * @param DateTime|string $datetime Date value.
	 * @return string
	 */
	private static function format_datetime( $datetime ): string {
		if ( ! $datetime instanceof DateTime ) {
			$datetime = new DateTime( (string) $datetime );
		}

		return Format::api_datetime( $datetime );
	}
}
