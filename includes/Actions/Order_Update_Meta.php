<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Order_Update_Meta
 */
class Action_Order_Update_Meta extends Action {

	/**
	 * The data items required by the action.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'order' ];

	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Update Custom Field', 'automatewoo' );
		$this->group       = __( 'Order', 'automatewoo' );
		$this->description = __( 'This action can add or update an order\'s custom field.', 'automatewoo' );
	}

	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {
		$meta_key = ( new Fields\Text() )
			->set_name( 'meta_key' )
			->set_title( __( 'Key', 'automatewoo' ) )
			->set_variable_validation()
			->set_required();

		$meta_value = ( new Fields\Text() )
			->set_name( 'meta_value' )
			->set_title( __( 'Value', 'automatewoo' ) )
			->set_variable_validation();

		$this->add_field( $meta_key );
		$this->add_field( $meta_value );
	}

	/**
	 * Run the action.
	 *
	 * @throws \Exception When an error occurs.
	 */
	public function run() {
		$order = $this->workflow->data_layer()->get_order();

		if ( ! $order ) {
			return;
		}

		$meta_key   = trim( $this->get_option( 'meta_key', true ) );
		$meta_value = $this->get_option( 'meta_value', true );

		// Make sure there is a meta key but a value is not required
		if ( $meta_key ) {
			$order->update_meta_data( $meta_key, $meta_value );
			$order->save();
		}
	}
}
