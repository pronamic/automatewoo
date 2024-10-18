<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Wishlist_View_Link
 */
class Variable_Wishlist_View_Link extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays a link to the wishlist.", 'automatewoo');
	}


	/**
	 * @param Wishlist $wishlist
	 * @param $parameters
	 * @return string
	 */
	function get_value( $wishlist, $parameters ) {
		return $wishlist->get_link();
	}

}
