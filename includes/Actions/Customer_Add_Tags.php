<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Customer_Add_Tags
 */
class Action_Customer_Add_Tags extends Action {

	/**
	 * The data items required by the action.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'customer' ];

	/**
	 * Method to set the action's admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Add Tags', 'automatewoo' );
		$this->group       = __( 'Customer', 'automatewoo' );
		$this->description = __( 'Please note that tags are not supported on guest customers.', 'automatewoo' );
	}

	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {
		$this->add_field( new Fields\User_Tags() );
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

		wp_add_object_terms( $customer->get_user_id(), $tags, 'user_tag' );
	}
}
