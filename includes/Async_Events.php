<?php

namespace AutomateWoo;

use AutomateWoo\Async_Events\Abstract_Async_Event;
use AutomateWoo\Async_Events\BookingCreated;
use AutomateWoo\Async_Events\BookingStatusChanged;
use AutomateWoo\Async_Events\MembershipCreated;

defined( 'ABSPATH' ) || exit;

/**
 * Manager and registry for async events.
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
final class Async_Events extends Registry {

	/**
	 * Static store of the includes map.
	 *
	 * @var array
	 */
	protected static $includes;

	/**
	 * Static store of loaded objects.
	 *
	 * @var array
	 */
	protected static $loaded = [];

	/**
	 * Load async event includes.
	 *
	 * @return array
	 */
	public static function load_includes() {
		$includes = [
			'order_created'        => 'AutomateWoo\Async_Events\Order_Created',
			'order_status_changed' => 'AutomateWoo\Async_Events\Order_Status_Changed',
			'order_paid'           => 'AutomateWoo\Async_Events\Order_Paid',
			'order_pending'        => 'AutomateWoo\Async_Events\Order_Pending',
			'review_approved'      => 'AutomateWoo\Async_Events\Review_Approved',
			'user_registered'      => 'AutomateWoo\Async_Events\User_Registered',
		];

		if ( Integrations::is_subscriptions_active() ) {
			$includes['subscription_created']                  = 'AutomateWoo\Async_Events\Subscription_Created';
			$includes['subscription_status_changed']           = 'AutomateWoo\Async_Events\Subscription_Status_Changed';
			$includes['subscription_renewal_payment_complete'] = 'AutomateWoo\Async_Events\Subscription_Renewal_Payment_Complete';
			$includes['subscription_renewal_payment_failed']   = 'AutomateWoo\Async_Events\Subscription_Renewal_Payment_Failed';
		}

		if ( Integrations::is_memberships_enabled() ) {
			$includes['membership_status_changed'] = 'AutomateWoo\Async_Events\Membership_Status_Changed';
			$includes[ MembershipCreated::NAME ]   = MembershipCreated::class;
		}

		if ( Integrations::is_bookings_active() ) {
			$includes[ BookingCreated::NAME ]       = BookingCreated::class;
			$includes[ BookingStatusChanged::NAME ] = BookingStatusChanged::class;
		}

		if ( Integrations::is_mc4wp() ) {
			$includes['mc4wp_form_success'] = 'AutomateWoo\Async_Events\MC4WP_Form_Success';
		}

		return apply_filters( 'automatewoo/async_events/includes', $includes );
	}

	/**
	 * Set the event_name prop after it's loaded.
	 *
	 * @param string               $name
	 * @param Abstract_Async_Event $async_event
	 */
	public static function after_loaded( $name, $async_event ) {
		$async_event->set_event_name( $name );
	}

	/**
	 * Get a list of events that are always required.
	 *
	 * @return array
	 */
	private static function get_always_required_events() {
		// order_created is required by conversion tracking and the order pending check
		$events = [
			'order_created',
		];

		return apply_filters( 'automatewoo/async_events/always_required_events', $events );
	}

	/**
	 * Determine which async events need to be loaded and initiated.
	 *
	 * Combines:
	 * - always required events
	 * - events required by triggers
	 * - event dependencies
	 *
	 * @return array
	 */
	private static function determine_required_events() {
		$required_events = self::get_always_required_events();

		// Add events required by active triggers
		foreach ( Triggers::get_all_active() as $trigger ) {

			$trigger_events = $trigger->get_required_async_events();

			if ( $trigger_events ) {
				$required_events = array_merge( $required_events, $trigger_events );
			}
		}

		$required_events = array_unique( $required_events );

		// Add event dependencies
		foreach ( $required_events as $event_name ) {
			$async_event_object = self::get( $event_name );

			if ( $async_event_object ) {
				$required_events = array_merge( $required_events, $async_event_object->get_event_dependencies() );
			}
		}

		return apply_filters( 'automatewoo/async_events/required_events', array_unique( $required_events ) );
	}

	/**
	 * Load and init all required async events.
	 */
	public static function init_required_events() {
		$events = self::determine_required_events();

		foreach ( $events as $event_name ) {
			$event = self::get( $event_name );

			if ( $event ) {
				$event->init();
			}
		}

		do_action( 'automatewoo/async_events/after_init_required_events', $events );
	}

	/**
	 * Checks that an async event object is valid.
	 *
	 * @param mixed $item
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function is_item_valid( $item ) {
		return $item instanceof Abstract_Async_Event;
	}

	/**
	 * Get the constructor args for an item.
	 *
	 * @param string $name
	 *
	 * @return array
	 */
	protected static function get_item_constructor_args( string $name ): array {
		switch ( $name ) {
			case BookingCreated::NAME:
			case BookingStatusChanged::NAME:
				return [ AW()->action_scheduler(), AW()->bookings_proxy() ];
			default:
				return [ AW()->action_scheduler() ];
		}
	}


	// Useful to override these methods for code hinting
	// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found

	/**
	 * Get all async events objects.
	 *
	 * @return Abstract_Async_Event[]
	 */
	public static function get_all() {
		return parent::get_all();
	}

	/**
	 * Get a single async event object.
	 *
	 * @param string $name
	 *
	 * @return Abstract_Async_Event|false
	 */
	public static function get( $name ) {
		return parent::get( $name );
	}

	// phpcs:enable
}
