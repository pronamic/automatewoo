<?php

namespace AutomateWoo\Rules;

use AutomateWoo\Cart;
use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * CartItemCount class.
 */
class CartItemCount extends Abstract_Number {

	/** @var string */
	public $data_item = DataTypes::CART;

	/** @var bool */
	public $support_floats = false;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Cart - Item Count', 'automatewoo' );
	}


	/**
	 * @param Cart   $cart
	 * @param string $compare
	 * @param mixed  $value
	 * @return bool
	 */
	public function validate( $cart, $compare, $value ) {
		return $this->validate_number( count( $cart->get_items() ), $compare, $value );
	}
}
