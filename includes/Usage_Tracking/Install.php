<?php

namespace AutomateWoo\Usage_Tracking;

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track when AutomateWoo is first installed.
 *
 * @package AutomateWoo\Usage_Tracking
 * @since   4.9.0
 */
class Install implements Event_Tracker_Interface {

	use Event_Helper;

	/**
	 * Initialize the tracking class with various hooks.
	 */
	public function init() {
		add_action( 'automatewoo_first_installed', [ $this, 'track_install' ] );
	}

	/**
	 * Track when AutomateWoo is first installed.
	 */
	public function track_install() {
		$this->record_event( 'first_installed' );
	}
}
