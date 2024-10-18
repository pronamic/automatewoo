<?php

namespace AutomateWoo\Async_Events;

use AutomateWoo\Review;

defined( 'ABSPATH' ) || exit;

/**
 * Class Review_Approved
 *
 * @since   4.8.0
 * @package AutomateWoo
 */
class Review_Approved extends Abstract_Async_Event {

	/**
	 * Init the event.
	 */
	public function init() {
		add_action( 'automatewoo/review/posted', [ $this, 'schedule_event' ] );
	}

	/**
	 * Get the async event hook name.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function get_hook_name(): string {
		return 'automatewoo/review/posted_async';
	}

	/**
	 * Schedule async event.
	 *
	 * @param Review $review
	 */
	public function schedule_event( $review ) {
		$this->create_async_event( [ $review->get_id() ] );
	}
}
