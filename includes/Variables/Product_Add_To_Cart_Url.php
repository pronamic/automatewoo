<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_Add_To_Cart_Url
 */
class Variable_Product_Add_To_Cart_Url extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays a link to the product that will also add the product to the users cart when clicked.', 'automatewoo' );
	}

	/**
	 * @param \WC_Product $product
	 * @param array       $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		// TODO what about variable products
		// SEMGREP WARNING EXPLANATION
		// URL is escaped. However, Semgrep only considers esc_url as valid.
		return esc_url_raw( add_query_arg( 'add-to-cart', $product->get_id(), $product->get_permalink() ) );
	}
}
