<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Admin URL variable.
 *
 * @class Variable_Order_Admin_Url
 */
class Variable_Order_Admin_Url extends Variable {


	/**
	 * Load admin details
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the admin URL of the order.', 'automatewoo' );
	}


	/**
	 * Get value method.
	 *
	 * @param \WC_Order $order
	 * @param array     $parameters
	 *
	 * @return mixed
	 */
	public function get_value( $order, $parameters ) {
		return $order->get_edit_order_url();
	}
}
