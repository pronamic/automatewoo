<?php

namespace AutomateWoo\Usage_Tracking;

use AutomateWoo\Rules;
use AutomateWoo\Triggers;
use AutomateWoo\Workflow;

/**
 * WorkflowTracksData trait.
 *
 * Used to convert workflow data into a single-dimensional array.
 *
 * @package AutomateWoo\Usage_Tracking
 * @since   5.0.0
 */
trait WorkflowTracksData {

	/**
	 * Get an array of data from the given workflow.
	 *
	 * @param Workflow $workflow The workflow that is running.
	 *
	 * @return array
	 */
	private function get_workflow_data( Workflow $workflow ) {
		$data = [
			'conversion_tracking_enabled' => $workflow->is_conversion_tracking_enabled(),
			'date_created'                => $workflow->get_date_created(),
			'ga_tracking_enabled'         => $workflow->is_ga_tracking_enabled(),
			'status'                      => $workflow->get_status(),
			'tracking_enabled'            => $workflow->is_tracking_enabled(),
			'unsubscribe_exempt'          => $workflow->is_exempt_from_unsubscribing(),
			'type'                        => $workflow->get_type(),
		];

		foreach ( $workflow->get_actions() as $key => $action ) {
			$this->recursively_add_items( $data, $key, $action->get_name(), 'action_' );
		}

		$data['trigger_name'] = $workflow->get_trigger_name();
		foreach ( $this->sanitize_trigger_options( $workflow ) as $var => $value ) {
			$this->recursively_add_items( $data, $var, $value, 'trigger_' );
		}

		// Drop named index for rule groups and rules to standardize property names.
		foreach ( array_values( $workflow->get_rule_data() ) as $key => $rules ) {
			$rules = array_map( [ $this, 'sanitize_rule' ], array_values( $rules ) );
			$this->recursively_add_items( $data, $key, $rules, 'rule_' );
		}

		return (array) apply_filters( 'automatewoo/usage_tracking/workflow_data', $data, $workflow );
	}

	/**
	 * Recursively add items to an array.
	 *
	 * @param array               $data   The array of data to add to.
	 * @param string              $key    The key to use.
	 * @param string|array|object $value  The value to add. Can be an array.
	 * @param string              $prefix A prefix to use for the data.
	 */
	private function recursively_add_items( &$data, $key, $value, $prefix = '' ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				$this->recursively_add_items( $data, $index, $item, "{$prefix}{$key}_" );
			}
		} elseif ( is_object( $value ) ) {
			foreach ( get_object_vars( $value ) as $index => $item ) {
				$this->recursively_add_items( $data, $index, $item, "{$prefix}{$key}_" );
			}
		} elseif ( is_string( $value ) ) {
			$data[ "{$prefix}{$key}" ] = $value;
		}
	}

	/**
	 * Keep only trigger option values that come from structured fields. Free-text values are blanked.
	 *
	 * @param Workflow $workflow The workflow.
	 *
	 * @return array
	 */
	private function sanitize_trigger_options( Workflow $workflow ) {
		$trigger = Triggers::get( $workflow->get_trigger_name() );
		$options = $workflow->get_trigger_options();

		foreach ( $options as $key => $value ) {
			$field = $trigger ? $trigger->get_field( $key ) : false;

			if ( ! $field || ! in_array( $field->get_type(), [ 'select', 'searchable-select', 'checkbox', 'number', 'category', 'term' ], true ) ) {
				$options[ $key ] = '';
			}
		}

		return $options;
	}

	/**
	 * Keep a rule value only when its rule type is structured. Email values are anonymized, everything else is blanked.
	 *
	 * @param array|mixed $item The rule data containing name, compare and value.
	 *
	 * @return array|mixed
	 */
	private function sanitize_rule( $item ) {
		if ( ! is_array( $item ) || ! isset( $item['value'] ) ) {
			return $item;
		}

		$name = $item['name'] ?? '';
		$rule = is_string( $name ) ? Rules::get( $name ) : false;

		if ( in_array( $name, [ 'customer_email', 'guest_email' ], true ) ) {
			$item['value'] = is_string( $item['value'] ) ? aw_anonymize_email( $item['value'] ) : '';
		} elseif ( ! $rule || ! in_array( $rule->type, [ 'select', 'object', 'number', 'bool', 'date' ], true ) ) {
			$item['value'] = '';
		}

		return $item;
	}
}
