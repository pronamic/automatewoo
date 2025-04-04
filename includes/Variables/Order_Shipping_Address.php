<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_Shipping_Address
 */
class Variable_Order_Shipping_Address extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the formatted shipping address for the order.', 'automatewoo' );
	}

	/**
	 * @param \WC_Order $order
	 * @param array     $parameters
	 * @return string
	 */
	public function get_value( $order, $parameters ) {
		return $order->get_formatted_shipping_address();
	}
}
