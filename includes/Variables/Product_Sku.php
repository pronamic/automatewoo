<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Product_Sku
 */
class Variable_Product_Sku extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the product's SKU.", 'automatewoo');
	}


	/**
	 * @param $product \WC_Product|\WC_Product_Variation
	 * @param $parameters
	 * @return string
	 */
	function get_value( $product, $parameters ) {
		return $product->get_sku();
	}
}
