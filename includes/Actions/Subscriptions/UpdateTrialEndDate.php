<?php

namespace AutomateWoo\Actions\Subscriptions;

defined( 'ABSPATH' ) || exit;

/**
 * Change a subscription's trial end date.
 *
 * @since 5.5.17
 */
class UpdateTrialEndDate extends AbstractEditDateItem {

	/**
	 * @var string Date field name to update for this subscription.
	 */
	protected $date_field = 'trial_end';

	/**
	 * @var string Subscription date to update.
	 */
	protected $subscription_date = 'trial_end';

	/**
	 * Explain to store admin what this action does via a unique title and description.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Update Trial End Date', 'automatewoo' );
		$this->description = __( 'Change a subscription\'s trial end date.', 'automatewoo' );
	}

	/**
	 * Load the fields required for the action.
	 */
	public function load_fields() {
		$date_field = ( new \AutomateWoo\Fields\Date() )
			->set_required()
			->set_name( 'new_trial_end_date' )
			->set_title( __( 'New Trial End Date', 'automatewoo' ) );

		$time_field = ( new \AutomateWoo\Fields\Time() )
			->set_required()
			->set_name( 'new_trial_end_time' )
			->set_title( __( 'New Trial End Time', 'automatewoo' ) );

		$this->add_field( $date_field );
		$this->add_field( $time_field );
	}

	/**
	 * Get the note on the subscription to record the end date change.
	 *
	 * @param string $new_trial_end_date Trial end date. The return value of @see $this->get_object_for_edit().
	 */
	protected function get_note( $new_trial_end_date ) {
		return sprintf(
			/* translators: %1$s: workflow name, %2$s: new trial end date, %3$s: workflow ID */
			__( '%1$s workflow run: updated trial end date to %2$s.  (Workflow ID: %3$d)', 'automatewoo' ),
			$this->workflow->get_title(),
			$new_trial_end_date,
			$this->workflow->get_id()
		);
	}
}
