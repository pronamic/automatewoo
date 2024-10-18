<?php

namespace AutomateWoo;

use AutomateWoo\Exceptions\InvalidArgument;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Trigger_Subscription_Before_End
 */
class Trigger_Subscription_Before_End extends Trigger_Subscription_Before_Renewal {

	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		$this->title        = __( 'Subscription Before End', 'automatewoo' );
		$this->description  = __( "This trigger runs once per day for any subscriptions that are due to expire/end on the workflow's target date. For example, if set to run 7 days before end, it would look for subscriptions that are due to end on the date exactly 7 days from now.", 'automatewoo' );
		$this->description .= ' ' . $this->get_description_text_workflow_not_immediate();

		$this->group = Subscription_Workflow_Helper::get_group_name();
	}

	/**
	 * Load fields.
	 */
	public function load_fields() {

		$days_before = ( new Fields\Positive_Number() )
			->set_name( 'days_before' )
			->set_title( __( 'Days before end', 'automatewoo' ) )
			->set_required();

		$this->add_field( $days_before );
		$this->add_field( $this->get_field_time_of_day() );
		$this->add_field( Subscription_Workflow_Helper::get_products_field() );
	}

	/**
	 * Get subscriptions that match the workflow's date params.
	 *
	 * @param Workflow $workflow
	 * @param int      $offset
	 * @param int      $limit
	 *
	 * @return int[] Array of subscription IDs.
	 *
	 * @throws InvalidArgument If workflow 'days before' option is not valid.
	 */
	protected function get_subscriptions_for_workflow( Workflow $workflow, int $offset, int $limit ) {
		$days_before_end = (int) $workflow->get_trigger_option( 'days_before' );
		$this->validate_positive_integer( $days_before_end );

		$date = ( new DateTime() )->add( new \DateInterval( "P{$days_before_end}D" ) );

		return $this->query_subscriptions_for_day(
			$date,
			'_schedule_end',
			[ 'wc-active', 'wc-pending-cancel' ],
			$offset,
			$limit
		);
	}

	/**
	 * Validate before a queued workflow event.
	 *
	 * Ensures that the subscription is either active or pending cancellation.
	 *
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		$subscription = $workflow->data_layer()->get_subscription();

		if ( ! $subscription || ! $subscription->has_status( [ 'active', 'pending-cancel' ] ) ) {
			return false;
		}

		return true;
	}
}
