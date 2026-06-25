<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Cart_Items
 */
class Cart_Items extends Product_Select_Rule_Abstract {

	/** @var string */
	public $data_item = DataTypes::CART;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Cart - Items', 'automatewoo' );
		parent::init();
	}


	/**
	 * @param \AutomateWoo\Cart $cart
	 * @param string            $compare
	 * @param mixed             $value
	 * @return bool
	 */
	public function validate( $cart, $compare, $value ) {
		$product = wc_get_product( absint( $value ) );

		if ( ! $product ) {
			return false;
		}

		$target_product_id = $product->get_id();
		$is_variation      = $product->is_type( 'variation' );

		$includes = false;

		foreach ( $cart->get_items() as $item ) {
			$id = $is_variation ? $item->get_variation_id() : $item->get_product_id();
			if ( $id === $target_product_id ) {
				$includes = true;
				break;
			}
		}

		switch ( $compare ) {
			case 'includes':
				return $includes;
			case 'not_includes':
				return ! $includes;
		}
	}
}
