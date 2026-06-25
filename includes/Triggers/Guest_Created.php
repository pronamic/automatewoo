<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Trigger_Guest_Created
 * @since 2.4.9
 */
class Trigger_Guest_Created extends Trigger {

	/** @var array */
	public $supplied_data_items = [ 'guest', 'customer' ];


	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'New Guest Captured', 'automatewoo' );
		$this->group       = __( 'Guests', 'automatewoo' );
		$this->description = __( 'This trigger fires when a new guest is captured. Usually this immediately after they enter email during the checkout process.', 'automatewoo' );
	}


	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_action( 'automatewoo/session_tracker/new_stored_guest', [ $this, 'catch_hooks' ], 100, 1 );
	}


	/**
	 * @param Guest $guest
	 */
	public function catch_hooks( $guest ) {
		$this->maybe_run(
			[
				'guest'    => $guest,
				'customer' => Customer_Factory::get_by_guest_id( $guest->get_id() ),
			]
		);
	}
}
