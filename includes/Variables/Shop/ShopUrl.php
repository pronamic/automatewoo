<?php

namespace AutomateWoo\Variables\Shop;

use AutomateWoo\Variable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is used to define the { shop.shop_url } variable.
 *
 * @since 5.4.0
 */
class ShopUrl extends Variable {

	/**
	 * Prepare details of the variable to display to the merchant.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the URL to the shop section of your site.', 'automatewoo' );
	}

	/**
	 * Returns variable value.
	 *
	 * @param array $parameters List of parameters used to build the variable.
	 *
	 * @return string Url to the shop section of the site.
	 */
	public function get_value( $parameters ) {
		return wc_get_page_permalink( 'shop' );
	}
}
