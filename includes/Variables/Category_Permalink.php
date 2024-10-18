<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Category_Permalink
 */
class Variable_Category_Permalink extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays a permalink to the category page.", 'automatewoo');
	}


	/**
	 * @param $category \WP_Term
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $category, $parameters ) {
		$link = get_term_link( $category );
		if ( ! $link instanceof \WP_Error ) {
			return $link;
		}
	}
}
