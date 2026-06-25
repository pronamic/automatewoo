<?php

namespace AutomateWoo\DataTypes;

use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ProductCategory data type class.
 */
class ProductCategory extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof WP_Term;
	}


	/**
	 * @param mixed $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->term_id;
	}


	/**
	 * @param int|string|null $compressed_item
	 * @param array           $compressed_data_layer
	 * @return WP_Term|false
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		$term = get_term( $compressed_item, 'product_cat' );
		if ( ! $term instanceof WP_Term ) {
			return false;
		}

		return $term;
	}
}
