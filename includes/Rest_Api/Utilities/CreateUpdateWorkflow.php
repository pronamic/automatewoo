<?php

namespace AutomateWoo\Rest_Api\Utilities;

use AutomateWoo\Actions;
use AutomateWoo\DateTime;
use AutomateWoo\Entity\Action;
use AutomateWoo\Entity\Rule;
use AutomateWoo\Entity\RuleGroup;
use AutomateWoo\Entity\Trigger;
use AutomateWoo\Entity\Workflow as WorkflowEntity;
use AutomateWoo\Entity\WorkflowTiming;
use AutomateWoo\Entity\WorkflowTimingDelayed;
use AutomateWoo\Entity\WorkflowTimingFixed;
use AutomateWoo\Entity\WorkflowTimingImmediate;
use AutomateWoo\Entity\WorkflowTimingScheduled;
use AutomateWoo\Entity\WorkflowTimingVariable;
use AutomateWoo\Format;
use AutomateWoo\Rules;
use AutomateWoo\Triggers;
use AutomateWoo\Workflow;
use AutomateWoo\Workflows;
use AutomateWoo\Workflows\Factory;
use AutomateWoo\Workflows\Status;
use WP_REST_Request;


/**
 * Trait for creating or updating a workflow through the REST API.
 *
 * @since   6.0.10
 * @package AutomateWoo\Rest_Api\Utilities
 */
trait CreateUpdateWorkflow {

	use GetWorkflow;

	/**
	 * Create a workflow from request data.
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return Workflow The workflow object.
	 * @throws RestException When the workflow creation fails.
	 */
	protected function create_workflow( $request ) {
		$workflow_entity = new WorkflowEntity(
			$this->get_trigger( $request->get_param( 'trigger' ) ),
			$request->get_param( 'type' ) ?? array_key_first( Workflows::get_types() ),
			$this->get_timing_from_data( $request->get_param( 'timing' ) )
		);

		$workflow_entity->set_status( new Status( $request->get_param( 'status' ) ?? Status::DISABLED ) )
			->set_title( $request->get_param( 'title' ) )
			->set_is_transactional( $request->get_param( 'is_transactional' ) ?? false )
			->set_is_tracking_enabled( $request->get_param( 'is_tracking_enabled' ) ?? false )
			->set_is_conversion_tracking_enabled( $request->get_param( 'is_conversion_tracking_enabled' ) ?? false )
			->set_ga_link_tracking( $request->get_param( 'google_analytics_link_tracking' ) ?? '' )
			->set_rule_groups( $this->get_rules( $request->get_param( 'rules' ) ) )
			->set_actions( $this->get_actions( $request->get_param( 'actions' ) ) )
			->set_order( $request->get_param( 'workflow_order' ) ?? 0 )
			->set_origin( 'rest-api' );

		try {
			$workflow = Factory::create( $workflow_entity );
		} catch ( InvalidWorkflow $e ) {
			throw new RestException(
				'rest_invalid_workflow',
				esc_html__( 'Invalid workflow data.', 'automatewoo' ),
				400
			);
		}

		return $workflow;
	}

	/**
	 * Create a workflow from request data.
	 *
	 * @param WP_REST_Request $request Full request object.
	 *
	 * @return Workflow The workflow object.
	 * @throws RestException When the workflow is not found or update fails.
	 */
	protected function update_workflow( $request ) {
		$workflow = $this->get_workflow( $request['id'] );

		if ( $request->get_param( 'trigger' ) ) {
			$trigger = $request->get_param( 'trigger' );
		} else {
			$trigger = [
				'name'    => $workflow->get_trigger_name(),
				'options' => $workflow->get_trigger_options(),
			];
		}

		if ( $request->get_param( 'timing' ) ) {
			$timing = $this->get_timing_from_data( $request->get_param( 'timing' ) );
		} else {
			$timing = $this->get_timing_from_workflow( $workflow );
		}

		$workflow_entity = new WorkflowEntity(
			$this->get_trigger( $trigger ),
			$request->get_param( 'type' ) ?? $workflow->get_type(),
			$timing
		);

		$workflow_entity->set_id( $request['id'] )
			->set_status( new Status( $request->get_param( 'status' ) ?? $workflow->get_status() ) )
			->set_title( $request->get_param( 'title' ) ?? $workflow->get_title() )
			->set_is_transactional( $request->get_param( 'is_transactional' ) ?? $workflow->is_transactional() )
			->set_is_tracking_enabled( $request->get_param( 'is_tracking_enabled' ) ?? $workflow->is_tracking_enabled() )
			->set_is_conversion_tracking_enabled( $request->get_param( 'is_conversion_tracking_enabled' ) ?? $workflow->is_conversion_tracking_enabled() )
			->set_ga_link_tracking( $request->get_param( 'google_analytics_link_tracking' ) ?? $workflow->get_ga_tracking_params() )
			->set_rule_groups( $this->get_rules( $request->get_param( 'rules' ) ) ?? $workflow->get_rule_data() )
			->set_actions( $this->get_actions( $request->get_param( 'actions' ) ) ?? $workflow->get_actions_data() )
			->set_order( $request->get_param( 'workflow_order' ) ?? $workflow->get_order() );

		try {
			$workflow = Factory::update( $workflow_entity );
		} catch ( InvalidWorkflow $e ) {
			throw new RestException(
				'rest_invalid_workflow',
				esc_html__( 'Invalid workflow data.', 'automatewoo' ),
				400
			);
		}

		return $workflow;
	}

	/**
	 * Get and sanitize trigger data from the request.
	 *
	 * @param array $trigger_data
	 *
	 * @return Trigger
	 *
	 * @throws RestException If an invalid or non-existing trigger is specified.
	 */
	protected function get_trigger( $trigger_data ) {
		$trigger = Triggers::get( $trigger_data['name'] );
		if ( ! $trigger ) {
			throw new RestException( 'rest_invalid_trigger', esc_html__( 'Invalid trigger!', 'automatewoo' ) );
		}

		// Sanitize trigger options.
		$trigger_options = $trigger_data['options'] ?? [];

		foreach ( $trigger_options as $name => $value ) {
			$field = $trigger->get_field( $name );

			if ( ! $field ) {
				throw new RestException(
					'rest_invalid_trigger_option',
					esc_html(
						/* translators: Trigger option name. */
						sprintf( __( 'Invalid trigger option: %s', 'automatewoo' ), $name )
					)
				);
			}

			$trigger_options[ $name ] = $field->sanitize_value( $value );
		}

		// Confirm all required trigger options are set.
		foreach ( $trigger->get_required_fields() as $name => $field ) {
			if ( ! isset( $trigger_options[ $name ] ) || empty( $trigger_options[ $name ] ) ) {
				throw new RestException(
					'rest_required_trigger_option',
					esc_html(
						sprintf(
							/* translators: %1$s trigger name, %2$s option name */
							__( 'Trigger %1$s requires the option %2$s to be set.', 'automatewoo' ),
							$trigger_data['name'],
							$name
						)
					)
				);
			}
		}

		return new Trigger( $trigger_data['name'], $trigger_options );
	}

	/**
	 * Get and sanitize rule data from the request.
	 *
	 * @param array $rules_data
	 *
	 * @return RuleGroup[] One or multiple groups of rules
	 *
	 * @throws RestException If an invalid or non-existing rule is specified.
	 */
	protected function get_rules( $rules_data ) {
		if ( empty( $rules_data ) ) {
			return [];
		}

		$workflow_rules = [];
		foreach ( $rules_data as $group_index => $rules ) {
			$rule_objects = array_map(
				function ( $rule_data ) {

					if ( ! Rules::get( $rule_data['name'] ) ) {
						throw new RestException(
							'rest_invalid_rule',
							esc_html(
								/* translators: Invalid rule name. */
								sprintf( __( 'Invalid rule: %s', 'automatewoo' ), $rule_data['name'] )
							)
						);
					}

					return new Rule(
						$rule_data['name'],
						$rule_data['compare'] ?? null,
						$rule_data['value'] ?? null
					);
				},
				$rules
			);

			$workflow_rules[ $group_index ] = new RuleGroup( $rule_objects );
		}

		return $workflow_rules;
	}

	/**
	 * Get and sanitize action data from the request.
	 *
	 * @param array $actions_data
	 *
	 * @return Action[]
	 *
	 * @throws RestException If an invalid or non-existing action is specified.
	 */
	protected function get_actions( $actions_data ) {
		if ( empty( $actions_data ) ) {
			return [];
		}

		$actions = [];

		foreach ( $actions_data as $data ) {
			$action = Actions::get( $data['action_name'] );
			if ( ! $action ) {
				throw new RestException(
					'rest_invalid_action',
					esc_html(
						/* translators: Invalid action name. */
						sprintf( __( 'Invalid action: %s', 'automatewoo' ), $data['action_name'] )
					)
				);
			}

			// Sanitize action options.
			$action_options = array_diff_key( $data, [ 'action_name' => '' ] );

			foreach ( $action_options as $name => $value ) {
				$field = $action->get_field( $name );

				if ( ! $field ) {
					throw new RestException(
						'rest_invalid_action_option',
						esc_html(
							/* translators: Action option name. */
							sprintf( __( 'Invalid action option: %s', 'automatewoo' ), $name )
						)
					);
				}

				$action_options[ $name ] = $field->sanitize_value( $value );
			}

			// Confirm all required trigger options are set.
			foreach ( $action->get_required_fields() as $name => $field ) {
				if ( ! isset( $action_options[ $name ] ) || empty( $action_options[ $name ] ) ) {
					throw new RestException(
						'rest_required_action_option',
						esc_html(
							sprintf(
								/* translators: %1$s action name, %2$s option name */
								__( 'Action %1$s requires the option %2$s to be set.', 'automatewoo' ),
								$data['action_name'],
								$name
							)
						)
					);
				}
			}

			$actions[] = new Action(
				$data['action_name'],
				$action_options
			);
		}

		return $actions;
	}

	/**
	 * Get timing data from an array of data.
	 *
	 * @param array $timing_options
	 *
	 * @return WorkflowTiming
	 *
	 * @throws RestException When the timing type or its settings are invalid or not recognized.
	 */
	protected function get_timing_from_data( $timing_options ) {
		if ( ! $timing_options ) {
			return new WorkflowTimingImmediate();
		}

		switch ( $timing_options['type'] ) {
			case WorkflowTimingImmediate::TYPE:
				$timing = new WorkflowTimingImmediate();
				break;

			case WorkflowTimingDelayed::TYPE:
				$delay_value = $timing_options['delay']['value'] ?? null;
				$delay_unit  = $timing_options['delay']['unit'] ?? WorkflowTimingDelayed::DELAY_UNIT_HOUR;

				$timing = new WorkflowTimingDelayed( $delay_value, $delay_unit );
				break;

			case WorkflowTimingScheduled::TYPE:
				$scheduled_hour   = 0;
				$scheduled_minute = 0;
				if ( ! empty( $timing_options['scheduled']['time_of_day'] ) ) {
					$scheduled_time   = explode( ':', $timing_options['scheduled']['time_of_day'] );
					$scheduled_hour   = isset( $scheduled_time[0] ) ? (int) $scheduled_time[0] : 0;
					$scheduled_minute = isset( $scheduled_time[1] ) ? (int) $scheduled_time[1] : 0;
				}

				$scheduled_days = $timing_options['scheduled']['days'] ?? [];
				$delay_value    = $timing_options['delay']['value'] ?? null;
				$delay_unit     = $timing_options['delay']['unit'] ?? WorkflowTimingDelayed::DELAY_UNIT_HOUR;

				$timing = new WorkflowTimingScheduled(
					array_map( Format::class . '::api_weekday_number', $scheduled_days ),
					$scheduled_hour,
					$scheduled_minute,
					$delay_value,
					$delay_unit
				);
				break;

			case WorkflowTimingFixed::TYPE:
				try {
					$fixed = new DateTime( $timing_options['datetime'] );
				} catch ( \Exception $e ) {
					throw new RestException( 'rest_invalid_datetime', esc_html__( 'Invalid timing date and/or time!', 'automatewoo' ) );
				}

				$timing = new WorkflowTimingFixed( $fixed );
				break;

			case WorkflowTimingVariable::TYPE:
				if ( empty( $timing_options['variable'] ) ) {
					throw new RestException( 'rest_timing_variable_not_specified', esc_html__( 'Timing variable not specified!', 'automatewoo' ) );
				}

				$timing = new WorkflowTimingVariable( $timing_options['variable'] );
				break;
		}

		return $timing;
	}

	/**
	 * Get timing data from an existing workflow.
	 *
	 * @param Workflow $workflow
	 *
	 * @return WorkflowTiming
	 */
	protected function get_timing_from_workflow( Workflow $workflow ) {

		switch ( $workflow->get_timing_type() ) {
			case WorkflowTimingImmediate::TYPE:
				$timing = new WorkflowTimingImmediate();
				break;

			case WorkflowTimingDelayed::TYPE:
				$timing = new WorkflowTimingDelayed(
					$workflow->get_timing_delay_number(),
					$workflow->get_timing_delay_unit()
				);
				break;

			case WorkflowTimingScheduled::TYPE:
				$scheduled_time   = explode( ':', $workflow->get_scheduled_time() );
				$scheduled_hour   = isset( $scheduled_time[0] ) ? (int) $scheduled_time[0] : 0;
				$scheduled_minute = isset( $scheduled_time[1] ) ? (int) $scheduled_time[1] : 0;

				$timing = new WorkflowTimingScheduled(
					$workflow->get_scheduled_days(),
					$scheduled_hour,
					$scheduled_minute,
					$workflow->get_timing_delay_number(),
					$workflow->get_timing_delay_unit()
				);
				break;

			case WorkflowTimingFixed::TYPE:
				$timing = new WorkflowTimingFixed( $workflow->get_fixed_time() );
				break;

			case WorkflowTimingVariable::TYPE:
				$timing = new WorkflowTimingVariable( $workflow->get_option( 'queue_datetime', false ) );
				break;
		}

		return $timing;
	}
}
