<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Product data type class.
 */
class Product extends AbstractDataType {

	/**
	 * @param $item
	 * @return bool
	 */
	function validate( $item ) {
		return $item instanceof WC_Product;
	}


	/**
	 * @param WC_Product $item
	 *
	 * @return int
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

		return wc_get_product( $compressed_item );
	}

}
