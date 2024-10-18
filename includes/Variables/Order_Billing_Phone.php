<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Billing_Phone
 */
class Variable_Order_Billing_Phone extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the billing phone number for the order.", 'automatewoo');
	}


	/**
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	function get_value( $order ) {
		return $order->get_billing_phone();
	}
}
