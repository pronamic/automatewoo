<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use AutomateWoo\Wishlist as WishlistModel;
use AutomateWoo\Wishlists;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Wishlist data type class.
 */
class Wishlist extends AbstractDataType {

	/**
	 * @param $item
	 * @return bool
	 */
	function validate( $item ) {
		return $item instanceof WishlistModel;
	}


	/**
	 * @param WishlistModel $item
	 * @return mixed
	 */
	function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return mixed
	 */
	function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		return Wishlists::get_wishlist( $compressed_item );
	}

}
