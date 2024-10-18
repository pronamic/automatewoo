<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Cart created date rule.
 *
 * @class Cart_Created_Date
 */
class Cart_Created_Date extends Abstract_Date {

	/**
	 * Data item type.
	 *
	 * @var string
	 */
	public $data_item = DataTypes::CART;

	/**
	 * Cart_Created_Date constructor.
	 */
	public function __construct() {
		$this->has_is_past_comparision = true;

		parent::__construct();
	}

	/**
	 * Init.
	 */
	public function init() {
		$this->title = __( 'Cart - Created Date', 'automatewoo' );
	}

	/**
	 * Validates rule.
	 *
	 * @param \AutomateWoo\Cart $cart    The cart.
	 * @param string            $compare What variables we're using to compare.
	 * @param array|null        $value   The values we have to compare. Null is only allowed when $compare is is_not_set.
	 *
	 * @return bool
	 */
	public function validate( $cart, $compare, $value = null ) {
		return $this->validate_date( $compare, $value, $cart->get_date_created() );
	}
}
