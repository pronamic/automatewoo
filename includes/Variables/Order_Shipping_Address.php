<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Shipping_Address
 */
class Variable_Order_Shipping_Address extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the formatted shipping address for the order.", 'automatewoo');
	}


	/**
	 * @param $order \WC_Order
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $order, $parameters ) {
		return $order->get_formatted_shipping_address();
	}
}
