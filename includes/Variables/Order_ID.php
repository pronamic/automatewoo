<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_ID
 */
class Variable_Order_ID extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the order's unique ID.", 'automatewoo');
	}


	/**
	 * @param $order \WC_Order
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $order, $parameters ) {
		return $order->get_id();
	}
}
