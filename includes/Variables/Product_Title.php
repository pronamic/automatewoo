<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Product_Title
 */
class Variable_Product_Title extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the product's title.", 'automatewoo' );
	}


	/**
	 * @param $product \WC_Product
	 * @param $parameters
	 * @return string
	 */
	function get_value( $product, $parameters ) {
		return $product->get_name();
	}
}
