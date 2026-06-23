<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_Categories
 */
class Variable_Product_Categories extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays a comma-separated list of the product's categories.", 'automatewoo' );
	}

	/**
	 * @param \WC_Product|\WC_Product_Variation $product
	 * @param array                             $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		$categories = wp_get_post_terms(
			$product_id,
			'product_cat',
			[
				'fields'  => 'names',
				'orderby' => 'name',
				'order'   => 'ASC',
			]
		);

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return '';
		}

		return implode( ', ', $categories );
	}
}
