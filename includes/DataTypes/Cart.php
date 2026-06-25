<?php

namespace AutomateWoo\DataTypes;

use AutomateWoo\Cart as CartModel;
use AutomateWoo\Cart_Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Cart
 */
class Cart extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	public function validate( $item ) {
		return is_a( $item, 'AutomateWoo\Cart' );
	}


	/**
	 * @param CartModel $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * @param int|string|null $compressed_item
	 * @param array           $compressed_data_layer
	 * @return mixed
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		$cart = Cart_Factory::get( $compressed_item );
		if ( $cart ) {
			return $cart;
		}

		// Cart may have been cleared but we will pass the cart object anyway
		// this behavior may change in the future
		$cart = new CartModel();
		$cart->set_id( $compressed_item );

		return $cart;
	}
}
