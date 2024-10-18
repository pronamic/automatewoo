<?php

namespace AutomateWoo\Async_Events;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;
use AutomateWoo\Proxies\Bookings;

defined( 'ABSPATH' ) || exit;

/**
 * Class BookingStatusChanged
 *
 * @since 5.3.0
 * @package AutomateWoo
 */
class BookingStatusChanged extends Abstract_Async_Event {

	const NAME = 'booking_status_changed';

	/**
	 * @var Bookings
	 */
	protected $bookings_proxy;

	/**
	 * BookingStatusChanged constructor.
	 *
	 * @since 6.0.18
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
		add_action( 'woocommerce_booking_status_changed', [ $this, 'schedule_event' ], 30, 3 );
	}

	/**
	 * Schedule bookings status change event for consumption by triggers.
	 *
	 * Doesn't dispatch for 'was-in-cart' status changes because this status isn't a real booking status and essentially
	 * functions as a 'trash' status. The was in cart is used when a booking cart item is removed from the cart.
	 *
	 * @param string $from       Previous status.
	 * @param string $to         New (current) status.
	 * @param int    $booking_id Booking id.
	 */
	public function schedule_event( string $from, string $to, int $booking_id ) {
		$was_in_cart = 'was-in-cart';
		if ( $to === $was_in_cart || $from === $was_in_cart ) {
			// Don't dispatch an event for 'was-in-cart' status changes
			return;
		}

		$booking = $this->bookings_proxy->get_booking( $booking_id );

		// When the the user is a guest and adds the booking to the cart, the booking is not associated with an order yet neither a customer.
		// So runnning this workflow for this booking will only log errors as the data layer needs a customer to run the workflow.
		// See BookingDataLayer::generate_booking_data_layer
		if ( ( ! $booking->get_order() || ! is_a( $booking->get_order(), 'WC_Order' ) ) && ! $booking->get_customer_id() && $to === 'in-cart' ) {
			return;
		}

		$this->create_async_event(
			[
				$booking_id,
				$from,
				$to,
			]
		);
	}
}
