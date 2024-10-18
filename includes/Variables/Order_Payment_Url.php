<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Payment_Url
 */
class Variable_Order_Payment_Url extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays a payment URL for the order.", 'automatewoo');
	}


	/**
	 * @param $order \WC_Order
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $order, $parameters ) {
		return $order->get_checkout_payment_url();
	}
}
