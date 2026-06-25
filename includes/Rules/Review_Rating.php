<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Review_Rating
 */
class Review_Rating extends Abstract_Number {

	/** @var string */
	public $data_item = 'review';

	/** @var bool */
	public $support_floats = false;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Review - Rating', 'automatewoo' );
	}


	/**
	 * @param \AutomateWoo\Review $review
	 * @param string              $compare
	 * @param mixed               $value
	 * @return bool
	 */
	public function validate( $review, $compare, $value ) {
		return $this->validate_number( $review->get_rating(), $compare, $value );
	}
}
