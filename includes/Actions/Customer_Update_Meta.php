<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Customer_Update_Meta
 */
class Action_Customer_Update_Meta extends Action {

	/** @var array */
	public $required_data_items = [ 'customer' ];


	/**
	 * Load admin details for the action.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Update Custom Field', 'automatewoo' );
		$this->group       = __( 'Customer', 'automatewoo' );
		$this->description = __( 'This action can add or update a customer\'s custom field. If the customer has an account, the user meta table is used. If the customer is a guest, the guest meta table is used.', 'automatewoo' );
	}


	/**
	 * Load the action fields.
	 */
	public function load_fields() {

		$meta_key = ( new Fields\Text() )
			->set_name( 'meta_key' )
			->set_title( __( 'Key', 'automatewoo' ) )
			->set_required()
			->set_variable_validation();

		$meta_value = ( new Fields\Text() )
			->set_name( 'meta_value' )
			->set_title( __( 'Value', 'automatewoo' ) )
			->set_variable_validation();

		$this->add_field( $meta_key );
		$this->add_field( $meta_value );
	}


	/**
	 * Run the action.
	 */
	public function run() {

		$customer = $this->workflow->data_layer()->get_customer();
		if ( ! $customer ) {
			return;
		}

		$meta_key   = trim( $this->get_option( 'meta_key', true ) );
		$meta_value = $this->get_option( 'meta_value', true );

		// Make sure there is a meta key but a value can be blank
		if ( $meta_key ) {
			$customer->update_legacy_meta( $meta_key, $meta_value );
		}
	}
}
