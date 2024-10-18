<?php

namespace AutomateWoo\Async_Events;

defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription_Status_Changed
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class Subscription_Status_Changed extends Abstract_Async_Event {

	/**
	 * Init the event.
	 */
	public function init() {
		add_action( 'automatewoo/subscription/status_changed', [ $this, 'schedule_event' ], 10, 3 );
	}

	/**
	 * Get the async event hook name.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function get_hook_name(): string {
		return 'automatewoo/subscription/status_changed_async';
	}

	/**
	 * Schedule async event.
	 *
	 * @param int    $subscription_id
	 * @param string $new_status
	 * @param string $old_status
	 */
	public function schedule_event( $subscription_id, $new_status, $old_status ) {
		$this->create_async_event(
			[
				$subscription_id,
				$new_status,
				$old_status,
			]
		);
	}
}
