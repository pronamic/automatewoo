<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Number
 */
class Variable_Order_Number extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the order number.", 'automatewoo');
	}


	/**
	 * @param $order \WC_Order
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $order, $parameters ) {
		return $order->get_order_number();
	}
}
