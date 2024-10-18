<?php

namespace AutomateWoo\Async_Events;

defined( 'ABSPATH' ) || exit;

/**
 * Class User_Registered
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class User_Registered extends Abstract_Async_Event {

	/**
	 * Init the event.
	 */
	public function init() {
		add_action( 'automatewoo/user_registered', [ $this, 'handle_user_registered' ] );
	}

	/**
	 * Handle user registered event.
	 *
	 * Async user registration hook, allows checkout and other third party plugins to add data before we run triggers.
	 *
	 * @param int $user_id
	 */
	public function handle_user_registered( int $user_id ) {
		$this->create_async_event( [ $user_id ] );
	}
}
