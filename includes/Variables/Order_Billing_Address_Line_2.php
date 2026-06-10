<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Order_Billing_Address_Line_2 class.
 *
 * @since 6.4.0
 *
 * @class Variable_Order_Billing_Address_Line_2
 */
class Variable_Order_Billing_Address_Line_2 extends Variable {

	/**
	 * Load description for variable in admin screen.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the second line of the billing address for the order.', 'automatewoo' );
	}

	/**
	 * Method: get_value() - returns the second line of the billing address.
	 *
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public function get_value( $order ) {
		return $order->get_billing_address_2();
	}
}
