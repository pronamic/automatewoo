<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use WP_Term;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ProductTag data type class.
 */
class ProductTag extends ProductCategory {

	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return WP_Term|false
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		$term = get_term( $compressed_item, 'product_tag' );
		if ( ! $term instanceof WP_Term ) {
			return false;
		}

		return $term;
	}

}
