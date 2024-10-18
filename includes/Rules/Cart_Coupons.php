<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cart_Coupons
 *
 * @package AutomateWoo\Rules
 */
class Cart_Coupons extends Order_Coupons {

	/**
	 * The rule's primary data item.
	 *
	 * @var string
	 */
	public $data_item = DataTypes::CART;

	/**
	 * Init.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Cart - Coupons', 'automatewoo' );
	}

	/**
	 * Validate the rule for a given cart.
	 *
	 * @param \AutomateWoo\Cart $cart
	 * @param string            $compare
	 * @param array             $expected_coupons
	 *
	 * @return bool
	 */
	public function validate( $cart, $compare, $expected_coupons ) {
		return $this->validate_select_case_insensitive( array_keys( $cart->get_coupons() ), $compare, $expected_coupons );
	}
}
