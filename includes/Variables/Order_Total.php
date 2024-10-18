<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable Order Total Class.
 *
 * @class Variable_Order_Total
 */
class Variable_Order_Total extends Variable_Abstract_Price {

	/**
	 * Load Admin Details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the total cost of the order.', 'automatewoo' );
	}


	/**
	 * Get Value Method.
	 *
	 * @param \WC_Order $order
	 * @param array     $parameters
	 *
	 * @return string
	 */
	public function get_value( $order, $parameters ) {
		return parent::format_amount( $order->get_total(), $parameters );
	}
}
