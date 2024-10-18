<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Trigger;
use AutomateWoo\Async_Events;
use AutomateWoo\Logger;
use AutomateWoo\Proxies\BookingsInterface;
use AutomateWoo\Triggers\Utilities\BookingsGroup;
use AutomateWoo\Async_Events\BookingCreated as BookingCreatedEvent;
use AutomateWoo\Triggers\Utilities\BookingDataLayer;

/**
 * @class BookingCreated
 *
 * @since 5.3.0
 */
class BookingCreated extends Trigger {

	use BookingsGroup;
	use BookingDataLayer;

	/**
	 * @var BookingsInterface Proxy for functionality from WooCommerce Bookings extension.
	 */
	protected $bookings_proxy;

	/**
	 * Async events required by the trigger.
	 *
	 * @var array|string
	 */
	protected $required_async_events = BookingCreatedEvent::NAME;

	/**
	 * Constructor
	 *
	 * @param BookingsInterface $bookings_proxy Bookings proxy class.
	 */
	public function __construct( BookingsInterface $bookings_proxy ) {
		$this->supplied_data_items = $this->get_supplied_data_items_for_booking();

		parent::__construct();

		$this->bookings_proxy = $bookings_proxy;
	}

	/**
	 * Declare our UI metadata.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Booking Created', 'automatewoo' );
		$this->description = __(
			'This trigger fires when a new booking is created. This includes bookings initiated by shoppers on store front end and manually created by admin users. This trigger doesn\'t fire for "in-cart" bookings and a valid customer is needed to trigger this job',
			'automatewoo'
		);
	}

	/**
	 * Register handlers to drive triggers from internal AW async event hook.
	 */
	public function register_hooks() {
		$async_event = Async_Events::get( BookingCreatedEvent::NAME );
		if ( $async_event ) {
			add_action( $async_event->get_hook_name(), [ $this, 'handle_booking_created' ], 10, 1 );
		}
	}

	/**
	 * Handle the booking created event.
	 *
	 * @param int $booking_id
	 */
	public function handle_booking_created( int $booking_id ) {
		try {
			$booking    = $this->bookings_proxy->get_booking( $booking_id );
			$data_layer = $this->generate_booking_data_layer( $booking );
		} catch ( \Exception $e ) {
			Logger::notice( 'bookings', $e->getMessage() );
			return;
		}

		$this->maybe_run( $data_layer );
	}
}
