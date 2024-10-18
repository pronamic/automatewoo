<?php

namespace AutomateWoo\Usage_Tracking;

use AutomateWoo\Exceptions\InvalidClass;

/**
 * Static Helper Class for tracks.
 *
 * @package AutomateWoo\Usage_Tracking
 * @since   4.9.0
 */
class Initializer {

	/**
	 * The tracks object.
	 *
	 * @var Tracks_Interface
	 */
	private static $tracks;

	/**
	 * Initialize our tracking classes.
	 *
	 * There are two kinds of data that we're tracking: events, referred to as "Tracks", and
	 * general store data, referred to as the "Tracker". Here we initialize both types of data.
	 */
	public static function init() {
		if ( ! apply_filters( 'automatewoo/usage_tracking/enabled', true ) || 'yes' !== get_option( 'woocommerce_allow_tracking', 'no' ) ) {
			return;
		}

		do_action( 'automatewoo/usage_tracking/init' );

		self::initialize_tracks();
		self::initialize_tracker();
	}

	/**
	 * Initialize the tracks object if needed.
	 */
	private static function maybe_initialize_tracks() {
		if ( null === self::$tracks ) {
			self::$tracks = new Tracks();
		}
	}

	/**
	 * Initialize our tracks classes.
	 *
	 * @throws InvalidClass When a class does not exist, or the proper interface is not implemented.
	 */
	private static function initialize_tracks() {
		self::maybe_initialize_tracks();

		// Allow add-ons to include their own classes for tracking.
		$addon_classes = (array) apply_filters( 'automatewoo/usage_tracking/addon_tracking_classes', [] );

		// Our own list of classes for event tracking.
		$classes = [
			Conversions::class,
			Install::class,
			Workflows::class,
		];

		// Instantiate each class.
		$classes = array_unique( array_merge( $addon_classes, $classes ) );
		foreach ( $classes as $class ) {
			self::validate_class( $class, Event_Tracker_Interface::class );

			/** @var Event_Tracker_Interface $instance */
			$instance = new $class();
			$instance->init();
			$instance->set_tracks( self::$tracks );
		}
	}

	/**
	 * Hook our custom tracker data to the regular WC tracker data.
	 */
	private static function initialize_tracker() {
		global $wpdb;

		$tracker = new Tracker( $wpdb );
		$tracker->init();
	}

	/**
	 * Validate that a class exists and that it implements the given interface.
	 *
	 * @param string $class     The class to validate.
	 * @param string $interface The interface the class should implement.
	 *
	 * @throws InvalidClass When the class is invalid.
	 */
	private static function validate_class( $class, $interface ) {
		if ( ! class_exists( $class ) ) {
			throw InvalidClass::does_not_exist( esc_html( $class ) );
		}

		$implements = class_implements( $class );
		if ( ! array_key_exists( $interface, $implements ) ) {
			throw InvalidClass::does_not_implement_interface( esc_html( $class ), esc_html( $interface ) );
		}
	}
}
