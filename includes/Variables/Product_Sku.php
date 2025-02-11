<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_Sku
 */
class Variable_Product_Sku extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the product's SKU.", 'automatewoo' );
	}

	/**
	 * @param \WC_Product|\WC_Product_Variation $product
	 * @param array                             $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		return $product->get_sku();
	}
}
