<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Trigger;
use AutomateWoo\Async_Events;
use AutomateWoo\Logger;
use AutomateWoo\Temporary_Data;
use AutomateWoo\Fields\BookingStatus;
use AutomateWoo\Proxies\BookingsInterface;
use AutomateWoo\Triggers\Utilities\BookingsGroup;
use AutomateWoo\Async_Events\BookingStatusChanged as BookingStatusChangedEvent;
use AutomateWoo\Triggers\Utilities\BookingDataLayer;

/**
 * @class BookingStatusChanged
 *
 * @since 5.3.0
 */
class BookingStatusChanged extends Trigger {

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
	protected $required_async_events = BookingStatusChangedEvent::NAME;

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
		$this->title       = __( 'Booking Status Changed', 'automatewoo' );
		$this->description = __(
			'This trigger fires when a booking status changes. Notice a valid customer is needed to trigger this job.',
			'automatewoo'
		);
	}

	/**
	 * Declare our trigger options.
	 */
	public function load_fields() {
		$from = ( new BookingStatus() )
			->set_title( __( 'Status changes from', 'automatewoo' ) )
			->set_name( 'booking_status_from' )
			->set_description( __( 'Select valid previous booking status values to trigger this workflow. Leave blank to allow any previous status. ', 'automatewoo' ) )
			->set_multiple();
		$this->add_field( $from );

		$to = ( new BookingStatus() )
			->set_title( __( 'Status changes to', 'automatewoo' ) )
			->set_name( 'booking_status_to' )
			->set_description( __( 'Select which booking status values will trigger this workflow. Leave blank to allow all.', 'automatewoo' ) )
			->set_multiple();
		$this->add_field( $to );

		$this->add_field_validate_queued_order_status();
	}

	/**
	 * Register handlers to drive triggers from internal AW async event hook.
	 */
	public function register_hooks() {
		$async_event = Async_Events::get( BookingStatusChangedEvent::NAME );
		if ( $async_event ) {
			add_action( $async_event->get_hook_name(), [ $this, 'handle_status_changed' ], 10, 3 );
		}
	}

	/**
	 * @param int    $booking_id
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function handle_status_changed( int $booking_id, string $old_status, string $new_status ) {
		try {
			$booking    = $this->bookings_proxy->get_booking( $booking_id );
			$data_layer = $this->generate_booking_data_layer( $booking );
		} catch ( \Exception $e ) {
			Logger::notice( 'bookings', $e->getMessage() );
			return;
		}

		// Freeze booking status values so we have the trigger-time value when running async.
		Temporary_Data::set( 'booking_trigger_from_status', $booking_id, $old_status );
		Temporary_Data::set( 'booking_trigger_to_status', $booking_id, $new_status );

		$this->maybe_run( $data_layer );
	}

	/**
	 * @param \AutomateWoo\Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		$booking             = $workflow->data_layer()->get_booking();
		$allowed_from_statii = $workflow->get_trigger_option( 'booking_status_from' );
		$allowed_to_statii   = $workflow->get_trigger_option( 'booking_status_to' );

		if ( ! $booking ) {
			return false;
		}

		// Defrost saved status data from when trigger fired.
		$from_status = Temporary_Data::get( 'booking_trigger_from_status', $booking->get_id() );
		$to_status   = Temporary_Data::get( 'booking_trigger_to_status', $booking->get_id() );

		if ( ! $this->validate_status_field( $allowed_from_statii, $from_status ) ) {
			return false;
		}

		if ( ! $this->validate_status_field( $allowed_to_statii, $to_status ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Ensures 'to' status has not changed while sitting in queue in case
	 * `validate_order_status_before_queued_run` is checked in Trigger the UI.
	 *
	 * @param \AutomateWoo\Workflow $workflow The workflow to validate
	 * @return bool True if it's valid
	 */
	public function validate_before_queued_event( $workflow ) {

		if ( ! $workflow ) {
			return false;
		}

		if ( $workflow->get_trigger_option( 'validate_order_status_before_queued_run' ) ) {
			$status_to = $workflow->get_trigger_option( 'booking_status_to' );
			$booking   = $workflow->data_layer()->get_booking();

			if ( ! $this->validate_status_field( $status_to, $booking->get_status() ) ) {
				return false;
			}
		}

		return true;
	}
}
