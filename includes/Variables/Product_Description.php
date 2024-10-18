<?php

namespace AutomateWoo;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_Description
 */
class Variable_Product_Description extends Variable {


	/**
	 * Method to set title, group, description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the description of the product or variation.', 'automatewoo' );
	}


	/**
	 * @param WC_Product $product
	 * @param array      $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		return $product->get_description();
	}
}
