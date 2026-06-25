<?php

namespace AutomateWoo\DataTypes;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product data type class.
 */
class Product extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof WC_Product;
	}


	/**
	 * @param WC_Product $item
	 *
	 * @return int
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

		return wc_get_product( $compressed_item );
	}
}
