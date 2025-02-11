<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_Parent_Sku
 * @since 2.9
 */
class Variable_Product_Parent_Sku extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the parent product's SKU.", 'automatewoo' );
	}

	/**
	 * @param \WC_Product $product
	 * @param array       $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		$parent_id = $product->get_parent_id();

		if ( $parent_id ) {
			$parent = wc_get_product( $parent_id );
			if ( $parent ) {
				return $parent->get_sku();
			}
		}

		return '';
	}
}
