<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Customer_Order_Count
 */
class Variable_Customer_Order_Count extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the total number of orders the customer has placed.', 'automatewoo' );
	}

	/**
	 * Get the value.
	 *
	 * @param Customer $customer
	 * @param array    $parameters
	 *
	 * @return int
	 */
	public function get_value( $customer, $parameters ) {
		return $customer->get_order_count();
	}
}
