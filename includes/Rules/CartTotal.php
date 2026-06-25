<?php

namespace AutomateWoo\Rules;

use AutomateWoo\Cart;
use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * CartTotal rule class.
 */
class CartTotal extends Abstract_Number {

	/** @var string  */
	public $data_item = DataTypes::CART;

	/** @var bool */
	public $support_floats = true;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Cart - Total', 'automatewoo' );
	}


	/**
	 * @param Cart   $cart
	 * @param string $compare
	 * @param mixed  $value
	 * @return bool
	 */
	public function validate( $cart, $compare, $value ) {
		return $this->validate_number( $cart->get_total(), $compare, $value );
	}
}
