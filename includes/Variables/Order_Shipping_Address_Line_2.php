<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Order_Shipping_Address_Line_2 class.
 *
 * @since 4.4.2
 *
 * @class Variable_Order_Shipping_Address_2
 */
class Variable_Order_Shipping_Address_Line_2 extends Variable {

	/**
	 * Load description for variable in admin screen.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the second line of the shipping address for the order.', 'automatewoo' );
	}


	/**
	 * Method: get_value() - returns the second line of the shipping address.
	 *
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public function get_value( $order ) {
		return $order->get_shipping_address_2();
	}
}
