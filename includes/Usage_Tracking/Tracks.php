<?php

namespace AutomateWoo\Usage_Tracking;

use WC_Tracks;

defined( 'ABSPATH' ) || exit;

/**
 * Class Tracks
 *
 * @package AutomateWoo\Usage_Tracking
 * @since 4.9.0
 */
class Tracks implements Tracks_Interface {

	const PREFIX = 'aw_';

	/**
	 * Record an event to track.
	 *
	 * @param string $event_name The event name to record.
	 * @param array  $properties Array of additional properties to included.
	 */
	public function record_event( $event_name, $properties = [] ) {
		if ( ! class_exists( 'WC_Tracks' ) ) {
			return;
		}

		$properties = $this->get_properties( $properties );
		WC_Tracks::record_event( self::PREFIX . $event_name, $properties );
	}

	/**
	 * Get the properties to use for an event.
	 *
	 * Adds default properties to every event, including the ability for Add-ons to add their own
	 * default properties.
	 *
	 * @param array $properties The array of properties for the event.
	 *
	 * @return array
	 */
	protected function get_properties( $properties = [] ) {
		// Add our own base properties, allowing add-ons to add base properties.
		$base_properties = array_merge(
			(array) apply_filters( 'automatewoo/usage_tracking/addon_base_properties', [] ),
			[
				'aw_version' => AUTOMATEWOO_VERSION,
			]
		);

		return wp_parse_args( $properties, $base_properties );
	}
}
