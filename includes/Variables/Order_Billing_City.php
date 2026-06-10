<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Order_Billing_City class.
 *
 * @since 6.4.0
 *
 * @class Variable_Order_Billing_City
 */
class Variable_Order_Billing_City extends Variable {

	/**
	 * Load description for variable in admin screen.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the billing city for the order.', 'automatewoo' );
	}

	/** Method: get_value() - returns billing city.
	 *
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public function get_value( $order ) {
		return $order->get_billing_city();
	}
}
