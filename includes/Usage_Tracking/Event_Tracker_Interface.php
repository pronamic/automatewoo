<?php

namespace AutomateWoo\Usage_Tracking;

/**
 * Interface describing an event tracker class.
 *
 * @package AutomateWoo\Usage_Tracking
 * @since 4.9.0
 */
interface Event_Tracker_Interface {

	/**
	 * Initialize the tracking class with various hooks.
	 */
	public function init();

	/**
	 * Set the Tracks object that will be used for tracking.
	 *
	 * @param Tracks_Interface $tracks
	 */
	public function set_tracks( Tracks_Interface $tracks );
}
