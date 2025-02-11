<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_Title
 */
class Variable_Product_Title extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the product's title.", 'automatewoo' );
	}

	/**
	 * @param \WC_Product $product
	 * @param array       $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		return $product->get_name();
	}
}
