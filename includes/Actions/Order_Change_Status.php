<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Order_Change_Status
 * @since 1.1.4
 */
class Action_Order_Change_Status extends Action {

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
		$this->title = __( 'Change Status', 'automatewoo' );
		$this->group = __( 'Order', 'automatewoo' );
	}

	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {
		$order_status = new Fields\Order_Status( false );
		$order_status->set_description( __( 'Order status will be changed to this.', 'automatewoo' ) );
		$order_status->set_required();

		$this->add_field( $order_status );
	}

	/**
	 * Run the action.
	 *
	 * @throws \Exception When an error occurs.
	 */
	public function run() {
		$order  = $this->workflow->data_layer()->get_order();
		$status = $this->get_option( 'order_status' );

		if ( ! $status || ! $order ) {
			return;
		}

		// translators: The workflow ID
		$note = sprintf( __( 'AutomateWoo workflow #%s.', 'automatewoo' ), $this->workflow->get_id() );

		$order->update_status( $status, $note );
	}
}
