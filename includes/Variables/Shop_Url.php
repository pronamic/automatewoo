<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Shop_Url
 */
class Variable_Shop_Url extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the URL to the home page of your shop.', 'automatewoo' );
	}

	/**
	 * @param array $parameters
	 * @return string
	 */
	public function get_value( $parameters ) {
		return home_url();
	}
}
