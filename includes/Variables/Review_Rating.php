<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Review_Rating
 */
class Variable_Review_Rating extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the review rating as a number.', 'automatewoo' );
	}

	/**
	 * @param Review $review
	 * @param array  $parameters
	 * @return string
	 */
	public function get_value( $review, $parameters ) {
		return $review->get_rating();
	}
}
