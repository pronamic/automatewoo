<?php

namespace AutomateWoo\Usage_Tracking;

/**
 * Event_Helper Trait.
 *
 * Use to provide Tracks event recording for an object.
 *
 * @since 4.9.0
 */
trait Event_Helper {

	/**
	 * The tracks object.
	 *
	 * @var Tracks_Interface
	 */
	protected $tracks = null;

	/**
	 * Set the Tracks object that will be used for tracking.
	 *
	 * @param Tracks_Interface $tracks
	 */
	public function set_tracks( Tracks_Interface $tracks ) {
		$this->tracks = $tracks;
	}

	/**
	 * Record an event using the Tracks instance
	 *
	 * @param string $event_name
	 * @param array  $properties
	 */
	private function record_event( $event_name, $properties = [] ) {
		// Ensure we have a valid object.
		if ( null === $this->tracks ) {
			$this->tracks = new Tracks();
		}

		$this->tracks->record_event( $event_name, $properties );
	}
}
