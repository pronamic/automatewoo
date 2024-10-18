<?php

namespace AutomateWoo\Actions\Subscriptions;

defined( 'ABSPATH' ) || exit;

/**
 * Change a subscription's next payment date.
 *
 * @since 5.4.0
 */
class UpdateNextPaymentDate extends AbstractEditDateItem {

	/**
	 * @var string Date field name to update for this subscription.
	 */
	protected $date_field = 'payment';

	/**
	 * @var string Subscription date to update.
	 */
	protected $subscription_date = 'next_payment';

	/**
	 * Explain to store admin what this action does via a unique title and description.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Update Next Payment Date', 'automatewoo' );
		$this->description = __( 'Change a subscription\'s next payment date.', 'automatewoo' );
	}

	/**
	 * Load the fields required for the action.
	 */
	public function load_fields() {
		$date_field = ( new \AutomateWoo\Fields\Date() )
			->set_required()
			->set_name( 'new_payment_date' )
			->set_title( __( 'New Payment Date', 'automatewoo' ) );

		$time_field = ( new \AutomateWoo\Fields\Time() )
			->set_required()
			->set_name( 'new_payment_time' )
			->set_title( __( 'New Payment Time', 'automatewoo' ) );

		$this->add_field( $date_field );
		$this->add_field( $time_field );
	}

	/**
	 * Get the note on the subscription to record the next payment date change.
	 *
	 * @param string $new_next_payment_date Next payment date. The return value of @see $this->get_object_for_edit().
	 */
	protected function get_note( $new_next_payment_date ) {
		return sprintf(
			/* translators: %1$s: workflow name, %2$s: new next payment date, %3$s: workflow ID */
			__( '%1$s workflow run: updated next payment date to %2$s.  (Workflow ID: %3$d)', 'automatewoo' ),
			$this->workflow->get_title(),
			$new_next_payment_date,
			$this->workflow->get_id()
		);
	}
}
