<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable Cart Total Class.
 *
 * @class Variable_Cart_Total
 */
class Variable_Cart_Total extends Variable_Abstract_Price {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the total cost of the cart.', 'automatewoo' );
	}

	/**
	 * Get Value Method
	 *
	 * @param Cart  $cart
	 * @param array $parameters
	 *
	 * @return string
	 */
	public function get_value( $cart, $parameters ) {
		return parent::format_amount( $cart->get_total(), $parameters );
	}
}
