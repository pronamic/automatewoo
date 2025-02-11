<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Review_Content
 */
class Variable_Review_Content extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the content of the review.', 'automatewoo' );
	}

	/**
	 * @param Review $review
	 * @param array  $parameters
	 * @return string
	 */
	public function get_value( $review, $parameters ) {
		return $review->get_content();
	}
}
