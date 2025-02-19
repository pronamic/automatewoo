<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Cart_Item_Count
 * @since 4.2
 */
class Variable_Cart_Item_Count extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the number of the items in cart.', 'automatewoo' );
	}

	/**
	 * @param Cart  $cart
	 * @param array $parameters
	 * @return string
	 */
	public function get_value( $cart, $parameters ) {
		return $cart->get_item_count();
	}
}
