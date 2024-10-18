<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Product_Add_To_Cart_Url
 */
class Variable_Product_Add_To_Cart_Url extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays a link to the product that will also add the product to the users cart when clicked.", 'automatewoo');
	}


	/**
	 * @param $product \WC_Product
	 * @param $parameters
	 * @return string
	 */
	function get_value( $product, $parameters ) {
		// TODO what about variable products
		// SEMGREP WARNING EXPLANATION
		// URL is escaped. However, Semgrep only considers esc_url as valid.
		return esc_url_raw( add_query_arg( 'add-to-cart', $product->get_id(), $product->get_permalink() ) );
	}

}
