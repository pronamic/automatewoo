<?php

namespace AutomateWoo;

/**
 * Class Review_Factory.
 *
 * @since 4.5
 * @package AutomateWoo
 */
class Review_Factory {

	/**
	 * Get a review object.
	 *
	 * @param \WP_Comment|int $comment Comment or comment ID.
	 *
	 * @return Review|bool
	 */
	public static function get( $comment ) {
		$review = new Review( $comment );
		return $review->exists ? $review : false;
	}
}
