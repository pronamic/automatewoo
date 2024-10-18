<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Shop_Url
 */
class Variable_Shop_Url extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the URL to the home page of your shop.", 'automatewoo');
	}


	/**
	 * @param $parameters
	 * @return string
	 */
	function get_value( $parameters ) {
		return home_url();
	}
}
