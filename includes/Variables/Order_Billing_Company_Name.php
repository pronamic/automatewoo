<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Order_Billing_Company_Name class.
 *
 * @since 6.4.0
 *
 * @class Variable_Order_Billing_Company_Name
 */
class Variable_Order_Billing_Company_Name extends Variable {

	/**
	 * Load description for variable in admin screen.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the billing company name for the order.', 'automatewoo' );
	}

	/**
	 * Method: get_value() - returns the billing company name.
	 *
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public function get_value( $order ) {
		return $order->get_billing_company();
	}
}
