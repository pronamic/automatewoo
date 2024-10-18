<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Customer_Remove_Tags
 */
class Action_Customer_Remove_Tags extends Action_Customer_Add_Tags {

	/**
	 * Method to load the action's fields.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Remove Tags', 'automatewoo' );
	}

	/**
	 * Run the action.
	 *
	 * @throws \Exception When an error occurs.
	 */
	public function run() {
		$customer = $this->workflow->data_layer()->get_customer();
		if ( ! $customer ) {
			return;
		}

		$tags = $this->get_option( 'user_tags' );

		if ( ! $customer->is_registered() || empty( $tags ) ) {
			return;
		}

		wp_remove_object_terms( $customer->get_user_id(), $tags, 'user_tag' );
	}
}
