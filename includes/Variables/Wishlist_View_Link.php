<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Wishlist_View_Link
 */
class Variable_Wishlist_View_Link extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays a link to the wishlist.', 'automatewoo' );
	}

	/**
	 * @param Wishlist $wishlist
	 * @param array    $parameters
	 * @return string
	 */
	public function get_value( $wishlist, $parameters ) {
		return $wishlist->get_link();
	}
}
