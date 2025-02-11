<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Category_Title
 */
class Variable_Category_Title extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the title of the category.', 'automatewoo' );
	}

	/**
	 * @param \WP_Term $category
	 * @param array    $parameters
	 * @return string
	 */
	public function get_value( $category, $parameters ) {
		return $category->name;
	}
}
