<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Category_Permalink
 */
class Variable_Category_Permalink extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays a permalink to the category page.', 'automatewoo' );
	}

	/**
	 * @param \WP_Term $category
	 * @param array    $parameters
	 * @return string
	 */
	public function get_value( $category, $parameters ) {
		$link = get_term_link( $category );
		if ( ! $link instanceof \WP_Error ) {
			return $link;
		}
	}
}
