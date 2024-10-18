<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Order_Shipping_Postcode class.
 *
 * @since 4.4.2
 *
 * @class Variable_Order_Shipping_Postcode
 */
class Variable_Order_Shipping_Postcode extends Variable {

	/**
	 * Load description for variable in admin screen.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the shipping postcode for the order.', 'automatewoo' );
	}

	/**
	 * Method: get_value() - get and return the shipping postcode.
	 *
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public function get_value( $order ) {
		return $order->get_shipping_postcode();
	}
}
