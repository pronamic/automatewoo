<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_ID
 */
class Variable_Product_ID extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the product's ID.", 'automatewoo' );
	}

	/**
	 * @param \WC_Product $product
	 * @param array       $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		return $product->get_id();
	}
}
