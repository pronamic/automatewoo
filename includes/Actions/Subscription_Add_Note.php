<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action for adding a note to a Subscription.
 *
 * @class Action_Subscription_Add_Note
 * @since 4.6.0
 */
class Action_Subscription_Add_Note extends Action_Order_Add_Note {

	/**
	 * Data items required for the action to run.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'subscription' ];

	/**
	 * Method to set title, group, description and other admin props.
	 */
	public function load_admin_details() {
		$this->title = __( 'Add Note', 'automatewoo' );
		$this->group = __( 'Subscription', 'automatewoo' );
	}

	/**
	 * Called when an action should be run.
	 */
	public function run() {
		$note_type    = $this->get_option( 'note_type' );
		$author       = $this->get_option( 'note_author' );
		$note         = $this->get_option( 'note', true );
		$subscription = $this->workflow->data_layer()->get_subscription();

		if ( ! $note || ! $note_type || ! $subscription ) {
			return;
		}

		if ( ! empty( $author ) && is_string( $author ) ) {
			$this->add_custom_author( $author );
		}

		$subscription->add_order_note( $note, 'customer' === $note_type, false );
	}
}
