<?php

namespace AutomateWoo\Usage_Tracking;

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
			'title'                       => $workflow->get_title(),
			'tracking_enabled'            => $workflow->is_tracking_enabled(),
			'unsubscribe_exempt'          => $workflow->is_exempt_from_unsubscribing(),
			'type'                        => $workflow->get_type(),
		];

		foreach ( $workflow->get_actions() as $key => $action ) {
			$this->recursively_add_items( $data, $key, $action->get_name(), 'action_' );
		}

		$data['trigger_name'] = $workflow->get_trigger_name();
		foreach ( $workflow->get_trigger_options() as $var => $value ) {
			$this->recursively_add_items( $data, $var, $value, 'trigger_' );
		}

		// Drop named index for rule groups and rules to standardize property names.
		foreach ( array_values( $workflow->get_rule_data() ) as $key => $rules ) {
			$this->recursively_add_items( $data, $key, array_values( $rules ), 'rule_' );
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
				$this->recursively_add_items( $data, $index, $this->maybe_anonymize_value( $item ), "{$prefix}{$key}_" );
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
	 * If the item name matches either `customer_email` or `customer_phone` then value will be anonymized.
	 *
	 * @param array|string $item The workflow item.
	 *
	 * @return array|string
	 */
	private function maybe_anonymize_value( $item ) {
		if (
			isset( $item['name'] ) &&
			in_array(
				$item['name'],
				array(
					'customer_email',
					'customer_phone',
				),
				true
			)
		) {
			$item['value'] = aw_anonymize_email( $item['value'] );
		}

		return $item;
	}
}
