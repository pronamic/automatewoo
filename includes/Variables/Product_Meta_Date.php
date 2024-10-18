<?php

namespace AutomateWoo;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_Meta_Date
 */
class Variable_Product_Meta_Date extends Variable_Order_Meta_Date {

	/**
	 * @param WC_Product $product
	 * @param array      $parameters
	 * @return string|bool
	 */
	public function get_value( $product, $parameters ) {
		if ( ! $parameters['key'] ) {
			return false;
		}

		$value = Clean::string( $product->get_meta( $parameters['key'] ) );
		return $this->format_datetime( $value, $parameters, true );
	}
}
