<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_Permalink
 */
class Variable_Product_Permalink extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the permalink to the product.', 'automatewoo' );
	}

	/**
	 * @param \WC_Product $product
	 * @param array       $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		return $product->get_permalink();
	}
}
