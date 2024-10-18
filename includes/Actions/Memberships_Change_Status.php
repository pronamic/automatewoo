<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Action_Memberships_Change_Status
 * @since 6.0.25
 */
class Action_Memberships_Change_Status extends Action_Memberships_Abstract {

	/** @var string[] $required_data_items The required data items for the Action */
	public $required_data_items = [ 'customer' ];

	/**
	 * Load admin details and set the Action title and description
	 *
	 * @return void
	 */
	public function load_admin_details() {
		parent::load_admin_details();

		$this->title       = __( "Update the status of a user's current membership plan", 'automatewoo' );
		$this->description = __( 'Changes the status of a users membership. The action will only run if a membership already exists for the user.', 'automatewoo' );
	}

	/**
	 * Setup fields used by the Action
	 *
	 * @return void
	 */
	public function load_fields() {
		$statuses = Memberships_Helper::get_membership_statuses();
		$plans    = Memberships_Helper::get_membership_plans();

		$plan = new Fields\Select();
		$plan->set_options( $plans );
		$plan->set_name( 'plan' );
		$plan->set_title( __( 'Membership Plan', 'automatewoo' ) );
		$plan->set_required();

		$from_status = new Fields\Select();
		$from_status->set_options( $statuses );
		$from_status->set_name( 'from_status' );
		$from_status->set_title( __( 'Current Status', 'automatewoo' ) );
		$from_status->set_placeholder( __( '[None]', 'automatewoo' ) );
		$from_status->set_description( __( 'Leave this blank to set the new status regardless of the existing status.', 'automatewoo' ) );

		$to_status = new Fields\Select();
		$to_status->set_options( $statuses );
		$to_status->set_name( 'to_status' );
		$to_status->set_title( __( 'New Status', 'automatewoo' ) );
		$to_status->set_required();

		$note = new Fields\Text_Area();
		$note->set_name( 'note' );
		$note->set_title( __( 'Note', 'automatewoo' ) );
		$note->set_variable_validation();

		$this->add_field( $plan );
		$this->add_field( $from_status );
		$this->add_field( $to_status );
		$this->add_field( $note );
	}

	/**
	 * Run the Action to update an existing membership status
	 *
	 * @return void
	 */
	public function run() {
		$customer       = $this->workflow->data_layer()->get_customer();
		$plan           = absint( $this->get_option( 'plan' ) );
		$current_status = $this->get_option( 'from_status' );
		$new_status     = $this->get_option( 'to_status' );
		$note           = $this->get_option( 'note' );

		if ( $current_status === $new_status ) {
			$this->workflow->log_action_note( $this, __( 'No change made to membership because the current status is the same as the new status.', 'automatewoo' ) );
			return;
		}

		$membership = $this->get_membership( $customer->get_user_id(), $plan );

		if ( ! $membership ) {
			$this->workflow->log_action_note( $this, __( 'Could not find an active membership for the customer.', 'automatewoo' ) );
			return;
		}

		if ( $current_status && $current_status !== $membership->get_status() ) {
			$this->workflow->log_action_note( $this, __( "Membership status doesn't match value specified in Action settings.", 'automatewoo' ) );
			return;
		}

		$this->set_status_transition_note( $note );

		$membership->update_status( $new_status );
	}

	/**
	 * Get a Membership for a specific user and plan
	 *
	 * @param int    $customer_id ID of the customer to get the membership plan for
	 * @param string $plan Slug for the membership plan to retreive
	 *
	 * @return \WC_Memberships_User_Membership|\WC_Memberships_Integration_Subscriptions_User_Membership|null The User Membership or null if not found
	 */
	public function get_membership( $customer_id, $plan ) {
		return wc_memberships_get_user_membership( $customer_id, $plan );
	}

	/**
	 * Prepend the membership status transition note with a user supplied note
	 *
	 * @param string $note The note to prepend to the membership status transition note
	 *
	 * @return void
	 */
	public function set_status_transition_note( $note ) {
		wc_memberships()->get_user_memberships_instance()->set_membership_status_transition_note( $note );
	}
}
