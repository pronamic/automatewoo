<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Shop_Title
 */
class Variable_Shop_Title extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays your shop's title.", 'automatewoo');
	}

	/**
	 * @param $parameters
	 * @return string
	 */
	function get_value( $parameters ) {
		return get_bloginfo('name');
	}
}
