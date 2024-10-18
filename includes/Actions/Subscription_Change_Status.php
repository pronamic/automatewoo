<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Subscription_Change_Status
 * @since 2.1.0
 */
class Action_Subscription_Change_Status extends Action {

	/** @var string[] */
	public $required_data_items = [ 'subscription' ];


	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		$this->title = __( 'Change Status', 'automatewoo' );
		$this->group = __( 'Subscription', 'automatewoo' );
	}

	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {

		$status = new Fields\Subscription_Status( false );
		$status->set_name( 'status' );
		$status->set_required();

		$this->add_field( $status );
	}

	/**
	 * Run the action.
	 */
	public function run() {

		$subscription = $this->workflow->data_layer()->get_subscription();
		$status       = $this->get_option( 'status' );

		if ( ! $status || ! $subscription ) {
			return;
		}

		$subscription->update_status(
			$status,
			sprintf(
				// translators: The Workflow ID
				__( 'Subscription status changed by AutomateWoo Workflow #%s.', 'automatewoo' ),
				$this->workflow->get_id()
			)
		);
	}
}
