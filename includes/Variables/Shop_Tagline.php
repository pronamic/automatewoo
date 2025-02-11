<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Shop_Tagline
 */
class Variable_Shop_Tagline extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays your shop's tag line.", 'automatewoo' );
	}

	/**
	 * @param array $parameters
	 * @return string
	 */
	public function get_value( $parameters ) {
		return get_bloginfo( 'description' );
	}
}
