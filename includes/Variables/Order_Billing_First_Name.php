<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Order_Billing_First_Name class.
 *
 * @since 6.4.0
 *
 * @class Variable_Order_Billing_First_Name
 */
class Variable_Order_Billing_First_Name extends Variable {

	/**
	 * Load description for variable in admin screen.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the billing first name of the order.', 'automatewoo' );
	}

	/**
	 * Method: get_value() - returns the billing address's first name.
	 *
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public function get_value( $order ) {
		return $order->get_billing_first_name();
	}
}
