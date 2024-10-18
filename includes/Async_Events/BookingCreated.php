<?php

namespace AutomateWoo\Async_Events;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;
use AutomateWoo\Exceptions\Exception as ExceptionInterface;
use AutomateWoo\Logger;
use AutomateWoo\Proxies\Bookings;
use WC_Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Class BookingCreated
 *
 * @since   5.3.0
 * @package AutomateWoo
 */
class BookingCreated extends Abstract_Async_Event {

	const NAME = 'booking_created';

	/**
	 * @var Bookings
	 */
	protected $bookings_proxy;

	/**
	 * BookingCreated constructor.
	 *
	 * @since 5.4.0
	 *
	 * @param ActionSchedulerInterface $action_scheduler
	 * @param Bookings                 $bookings_proxy
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, Bookings $bookings_proxy ) {
		$this->bookings_proxy = $bookings_proxy;

		parent::__construct( $action_scheduler );
	}

	/**
	 * Init the event.
	 */
	public function init() {
		add_action( 'woocommerce_new_booking', [ $this, 'handle_new_booking' ], 30, 1 );
		add_action( 'woocommerce_booking_status_changed', [ $this, 'handle_booking_status_changed' ], 30, 3 );
		add_action( $this->get_interim_hook_name(), [ $this, 'handle_interim_booking_created_event' ] );
	}

	/**
	 * Dispatch async event for consumption by triggers.
	 *
	 * @since 5.4.0
	 *
	 * @param int $booking_id Booking id.
	 */
	public function handle_new_booking( int $booking_id ) {
		try {
			$booking = $this->bookings_proxy->get_booking( $booking_id );

			if ( in_array( $booking->get_status(), $this->bookings_proxy->get_draft_booking_statuses(), true ) ) {
				// Booking is not considered created yet
				return;
			}

			// When a booking is created without an order and customer, it's not possible to run the workflow,
			// it  will only log errors as the data layer needs a customer to run the workflow.
			// See BookingDataLayer::generate_booking_data_layer
			if ( ( ! $booking->get_order() || ! is_a( $booking->get_order(), 'WC_Order' ) ) && ! $booking->get_customer_id() && $booking->get_status() === 'confirmed' ) {
				return;
			}

			$this->dispatch_interim_booking_created_event( $booking_id );
		} catch ( ExceptionInterface $e ) {
			Logger::notice( 'bookings', $e->getMessage() );
		}
	}

	/**
	 * Listens for when a booking status transitions from a "draft" type to a "non-draft" type.
	 *
	 * @since 5.4.0
	 *
	 * @param string $old_status
	 * @param string $new_status
	 * @param int    $booking_id Booking id.
	 */
	public function handle_booking_status_changed( string $old_status, string $new_status, int $booking_id ) {
		if (
			in_array( $old_status, $this->bookings_proxy->get_draft_booking_statuses(), true ) &&
			! in_array( $new_status, $this->bookings_proxy->get_draft_booking_statuses(), true )
		) {
			// Only consider the booking to be "created" if it transitions from "draft" to a "non-draft" status.
			$this->dispatch_interim_booking_created_event( $booking_id );
		}
	}

	/**
	 * Dispatch an interim scheduled action to ensure we don't interfere with the initial booking status change and
	 * creation hooks.
	 *
	 * Calling ::save() on a booking object during a complex booking life-cycle event could cause unintended side-effects.
	 *
	 * @since 5.4.0
	 *
	 * @param int $booking_id
	 */
	protected function dispatch_interim_booking_created_event( int $booking_id ) {
		$this->action_scheduler->enqueue_async_action( $this->get_interim_hook_name(), [ $booking_id ] );
	}

	/**
	 * Get the interim async event hook name.
	 *
	 * @see BookingCreated::dispatch_interim_booking_created_event()
	 *
	 * @since 5.4.0
	 *
	 * @return string
	 */
	protected function get_interim_hook_name(): string {
		return "{$this->get_hook_name()}/interim";
	}

	/**
	 * Handle the interim booking created hook.
	 *
	 * @since 5.4.0
	 *
	 * @param int $booking_id
	 */
	public function handle_interim_booking_created_event( int $booking_id ) {
		try {
			$booking = $this->bookings_proxy->get_booking( $booking_id );
			$this->dispatch_final_booking_created_event( $booking );
		} catch ( ExceptionInterface $e ) {
			Logger::notice( 'bookings', $e->getMessage() );
		}
	}

	/**
	 * Dispatch the final booking created event but only allow one to fire per booking.
	 *
	 * @param WC_Booking $booking
	 */
	protected function dispatch_final_booking_created_event( WC_Booking $booking ) {
		// Use a meta check to prevent duplicates
		$meta_key = '_automatewoo_is_created';
		if ( $booking->get_meta( $meta_key ) ) {
			return;
		}

		$booking->update_meta_data( $meta_key, true );
		$booking->save();

		// Dispatch actual async hook
		do_action( $this->get_hook_name(), $booking->get_id() );
	}
}
