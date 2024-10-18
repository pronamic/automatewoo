<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Review_Content
 */
class Variable_Review_Content extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the content of the review.", 'automatewoo');
	}


	/**
	 * @param $review Review
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $review, $parameters ) {
		return $review->get_content();
	}
}
