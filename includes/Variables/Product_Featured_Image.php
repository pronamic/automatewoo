<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * @class Variable_Product_Featured_Image
 */
class Variable_Product_Featured_Image extends Variable {


	/**
	 * Load admin details
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the product's featured image.", 'automatewoo' );
	}


	/**
	 * Get the value of this variable.
	 *
	 * @param \WC_Product $product
	 * @param mixed       $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		return $product->get_image( 'woocommerce_thumbnail' );
	}
}
