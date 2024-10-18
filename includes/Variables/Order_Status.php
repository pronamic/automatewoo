<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Order_Status
 */
class Variable_Order_Status extends Variable {


	/**
	 * Load the admin details for this variable
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the status of the order.', 'automatewoo' );
	}


	/**
	 * Get the Order Status Name
	 *
	 * @param \WC_Order $order The Order to get the status
	 *
	 * @return string The Order Status Name
	 */
	public function get_value( $order ) {
		return wc_get_order_status_name( $order->get_status() );
	}
}
