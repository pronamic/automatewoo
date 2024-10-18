<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Shop_Tagline
 */
class Variable_Shop_Tagline extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays your shop's tag line.", 'automatewoo');
	}


	/**
	 * @param $parameters
	 * @return string
	 */
	function get_value( $parameters ) {
		return get_bloginfo('description');
	}
}
