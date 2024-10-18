<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable_Order_Subtotal class.
 *
 * @since 4.6.0
 */
class Variable_Order_Subtotal extends Variable_Abstract_Price {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the order subtotal.', 'automatewoo' );
	}

	/**
	 * Get the value of this variable.
	 *
	 * @param \WC_Order $order
	 * @param array     $parameters
	 *
	 * @return string
	 */
	public function get_value( $order, $parameters ) {
		return parent::format_amount( $order->get_subtotal(), $parameters );
	}
}
