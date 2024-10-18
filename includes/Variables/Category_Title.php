<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Category_Title
 */
class Variable_Category_Title extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the title of the category.", 'automatewoo');
	}


	/**
	 * @param $category \WP_Term
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $category, $parameters ) {
		return $category->name;
	}
}
