<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Shop_Title
 */
class Variable_Shop_Title extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays your shop's title.", 'automatewoo' );
	}

	/**
	 * @param array $parameters
	 * @return string
	 */
	public function get_value( $parameters ) {
		return get_bloginfo( 'name' );
	}
}
