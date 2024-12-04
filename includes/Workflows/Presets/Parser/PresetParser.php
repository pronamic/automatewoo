<?php

namespace AutomateWoo\Workflows\Presets\Parser;

use AutomateWoo\Actions;
use AutomateWoo\DateTime;
use AutomateWoo\Entity\Action;
use AutomateWoo\Entity\Rule;
use AutomateWoo\Entity\RuleGroup;
use AutomateWoo\Entity\Trigger;
use AutomateWoo\Entity\Workflow;
use AutomateWoo\Entity\WorkflowTiming;
use AutomateWoo\Entity\WorkflowTimingDelayed;
use AutomateWoo\Entity\WorkflowTimingFixed;
use AutomateWoo\Entity\WorkflowTimingImmediate;
use AutomateWoo\Entity\WorkflowTimingScheduled;
use AutomateWoo\Entity\WorkflowTimingVariable;
use AutomateWoo\Rules;
use AutomateWoo\Triggers;
use AutomateWoo\Workflows\Presets\PresetInterface;
use AutomateWoo\Workflows\Status;

/**
 * @class PresetParser
 * @since 5.1.0
 */
class PresetParser implements PresetParserInterface {

	/**
	 * Parses the preset data and returns a workflow entity based on it
	 *
	 * @param PresetInterface $preset
	 *
	 * @return Workflow
	 *
	 * @throws ParserException If there are any errors parsing the preset.
	 */
	public function parse( PresetInterface $preset ) {
		$workflow = new Workflow(
			$this->extract_trigger( $preset->get( 'trigger' ) ),
			$preset->get( 'type', Workflow::TYPE_AUTOMATIC ),
			$this->extract_workflow_timing( $preset->get( 'timing' ) )
		);

		$workflow->set_status( new Status( Status::DISABLED ) )
			->set_title( $preset->get( 'title', 'Untitled Workflow' ) )
			->set_is_tracking_enabled( $preset->get( 'is_tracking_enabled', false ) )
			->set_is_conversion_tracking_enabled( $preset->get( 'is_conversion_tracking_enabled', false ) )
			->set_ga_link_tracking( $preset->get( 'ga_link_tracking', '' ) )
			->set_is_transactional( $preset->get( 'is_transactional' ) )
			->set_rule_groups( $this->extract_rules( $preset->get( 'rules' ) ) )
			->set_actions( $this->extract_actions( $preset->get( 'actions' ) ) )
			->set_origin( $preset->get_name() );

		return $workflow;
	}

	/**
	 * @param array $trigger_data
	 *
	 * @return Trigger
	 *
	 * @throws ParserException If an invalid or non-existing trigger is specified.
	 */
	protected function extract_trigger( $trigger_data ) {
		if ( empty( $trigger_data['name'] ) || ! Triggers::get( $trigger_data['name'] ) ) {
			throw new ParserException( 'Invalid trigger!' );
		}

		$trigger_name    = $trigger_data['name'];
		$trigger_options = $trigger_data['options'] ?? [];

		return new Trigger( $trigger_name, $trigger_options );
	}

	/**
	 * @param array $actions_data
	 *
	 * @return Action[]
	 *
	 * @throws ParserException If an invalid or non-existing action is specified.
	 */
	protected function extract_actions( $actions_data ) {
		if ( empty( $actions_data ) ) {
			return [];
		}

		return array_map(
			function ( $action_data ) {

				if ( empty( $action_data['name'] ) || ! Actions::get( $action_data['name'] ) ) {
					throw new ParserException( 'Invalid action!' );
				}

				$action_name    = $action_data['name'];
				$action_options = $action_data['options'] ?? [];

				return new Action( $action_name, $action_options );
			},
			$actions_data
		);
	}

	/**
	 * @param array $rules_data
	 *
	 * @return RuleGroup[] One or multiple groups of rules
	 *
	 * @throws ParserException If an invalid or non-existing rule is specified.
	 */
	protected function extract_rules( $rules_data ) {
		if ( empty( $rules_data ) ) {
			return [];
		}

		$workflow_rules = [];
		foreach ( $rules_data as $group_index => $rules ) {
			$rule_objects = array_map(
				function ( $rule_data ) {

					if ( empty( $rule_data['name'] ) || ! Rules::get( $rule_data['name'] ) ) {
						throw new ParserException( 'Invalid rule!' );
					}

					$rule_name    = $rule_data['name'];
					$rule_compare = $rule_data['compare'] ?? null;
					$rule_value   = $rule_data['value'] ?? null;

					return new Rule( $rule_name, $rule_compare, $rule_value );
				},
				$rules
			);

			$workflow_rules[ $group_index ] = new RuleGroup( $rule_objects );
		}

		return $workflow_rules;
	}

	/**
	 * @param array $timing_options
	 *
	 * @return WorkflowTiming
	 *
	 * @throws ParserException When the timing type or its options are invalid or not recognized.
	 */
	protected function extract_workflow_timing( $timing_options ) {
		if ( empty( $timing_options['type'] ) ) {
			throw new ParserException( 'Timing type not specified!' );
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
				if ( ! empty( $timing_options['scheduled_time'] ) ) {
					$scheduled_time   = explode( ':', $timing_options['scheduled_time'] );
					$scheduled_hour   = isset( $scheduled_time[0] ) ? (int) $scheduled_time[0] : 0;
					$scheduled_minute = isset( $scheduled_time[1] ) ? (int) $scheduled_time[1] : 0;
				}

				$scheduled_days = $timing_options['scheduled_day'] ?? null;

				$delay_value = $timing_options['delay']['value'] ?? null;
				$delay_unit  = $timing_options['delay']['unit'] ?? WorkflowTimingDelayed::DELAY_UNIT_HOUR;

				$timing = new WorkflowTimingScheduled(
					$scheduled_days,
					$scheduled_hour,
					$scheduled_minute,
					$delay_value,
					$delay_unit
				);
				break;

			case WorkflowTimingFixed::TYPE:
				if ( empty( $timing_options['fixed_date'] ) || empty( $timing_options['fixed_time'] ) ) {
					throw new ParserException( 'Missing date and/or time!' );
				}

				try {
					$date_string = sprintf( '%sT%s', $timing_options['fixed_date'], $timing_options['fixed_time'] );
					$fixed_date  = new DateTime( $date_string );
				} catch ( \Exception $e ) {
					throw new ParserException( 'Invalid date and/or time!' );
				}

				$timing = new WorkflowTimingFixed( $fixed_date );
				break;

			case WorkflowTimingVariable::TYPE:
				if ( empty( $timing_options['variable'] ) ) {
					throw new ParserException( 'Variable not specified!' );
				}

				$timing = new WorkflowTimingVariable( $timing_options['variable'] );
				break;

			default:
				throw new ParserException( 'Unexpected timing type!' );
		}

		return $timing;
	}
}
