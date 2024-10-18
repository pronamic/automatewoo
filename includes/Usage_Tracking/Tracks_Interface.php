<?php

namespace AutomateWoo\Usage_Tracking;

/**
 * Tracks Interface
 *
 * @package AutomateWoo\Usage_Tracking
 * @since   4.9.0
 */
interface Tracks_Interface {

	/**
	 * Record an event to track.
	 *
	 * @param string $event_name The event name to record.
	 * @param array  $properties Array of additional properties to included.
	 */
	public function record_event( $event_name, $properties );
}
