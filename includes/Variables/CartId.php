<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Cart;
use AutomateWoo\Variable;

defined( 'ABSPATH' ) || exit;

/**
 * Class CartId.
 *
 * @since 5.2.0
 */
class CartId extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the unique ID of the cart.', 'automatewoo' );
	}

	/**
	 * Get the variable's value for a given cart.
	 *
	 * @param Cart  $cart
	 * @param array $parameters
	 *
	 * @return string
	 */
	public function get_value( $cart, $parameters ) {
		return (string) $cart->get_id();
	}
}
