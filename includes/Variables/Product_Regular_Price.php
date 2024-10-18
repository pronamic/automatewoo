<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable Product Regular Price.
 *
 * @class Variable_Product_Regular_Price
 */
class Variable_Product_Regular_Price extends Variable_Abstract_Price {

	/**
	 * Load Admin Details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( "Displays the product's regular price.", 'automatewoo' );
	}

	/**
	 * Get Value Method.
	 *
	 * @param \WC_Product $product
	 * @param array       $parameters
	 *
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		return parent::format_amount( $product->get_regular_price(), $parameters );
	}
}
