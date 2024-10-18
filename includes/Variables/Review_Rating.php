<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Review_Rating
 */
class Variable_Review_Rating extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the review rating as a number.", 'automatewoo');
	}


	/**
	 * @param $review Review
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $review, $parameters ) {
		return $review->get_rating();
	}
}
