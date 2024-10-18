<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Wishlist_Itemscount
 */
class Variable_Wishlist_Itemscount extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the number of items in the wishlist.", 'automatewoo');
	}


	/**
	 * @param Wishlist $wishlist
	 * @param $parameters
	 * @return string
	 */
	function get_value( $wishlist, $parameters ) {

		if ( ! is_array( $wishlist->get_items() ) ) {
			return 0;
		}

		return count( $wishlist->get_items() );
	}
}
