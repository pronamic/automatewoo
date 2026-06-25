<?php

namespace AutomateWoo\DataTypes;

use AutomateWoo\Wishlist as WishlistModel;
use AutomateWoo\Wishlists;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wishlist data type class.
 */
class Wishlist extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof WishlistModel;
	}


	/**
	 * @param WishlistModel $item
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

		return Wishlists::get_wishlist( $compressed_item );
	}
}
