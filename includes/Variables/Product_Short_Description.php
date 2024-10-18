<?php

namespace AutomateWoo;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_Short_Description
 */
class Variable_Product_Short_Description extends Variable {

	/**
	 * Method to set title, group, description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the product's short description.", 'automatewoo' );
	}


	/**
	 * @param WC_Product $product
	 * @param array      $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		return $product->get_short_description();
	}
}
