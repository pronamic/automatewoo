<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\Cart;
use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * CartItemCount class.
 */
class CartItemCount extends Abstract_Number {

	public $data_item = DataTypes::CART;

	public $support_floats = false;


	function init() {
		$this->title = __( 'Cart - Item Count', 'automatewoo' );
	}


	/**
	 * @param Cart $cart
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $cart, $compare, $value ) {
		return $this->validate_number( count( $cart->get_items() ), $compare, $value );
	}

}
