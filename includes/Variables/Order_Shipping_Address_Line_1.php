<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Order_Shipping_Address_Line_1 class.
 *
 * @since 4.4.2
 *
 * @class Variable_Order_Shipping_Address_1
 */
class Variable_Order_Shipping_Address_Line_1 extends Variable {

	/**
	 * Load description for variable in admin screen.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the first line of the order shipping address.', 'automatewoo' );
	}

	/**
	 * Method: get_value() - returns the first line of the shipping address.
	 *
	 * @param \WC_Order $order
	 *
	 * @return mixed
	 */
	public function get_value( $order ) {
		return $order->get_shipping_address_1();
	}
}
