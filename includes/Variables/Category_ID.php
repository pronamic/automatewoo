<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Category_ID
 */
class Variable_Category_ID extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the ID of the category.", 'automatewoo');
	}


	/**
	 * @param $category \WP_Term
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $category, $parameters ) {
		return $category->term_id;
	}
}
