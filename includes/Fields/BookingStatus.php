<?php

namespace AutomateWoo\Fields;

/**
 * @class BookingStatus
 */
class BookingStatus extends Select {

	/**
	 * @var $name Field type name.
	 */
	protected $name = 'booking_status';

	/**
	 * @param bool $allow_all
	 */
	public function __construct( $allow_all = true ) {
		parent::__construct( true );

		$this->set_title( __( 'Booking status', 'automatewoo' ) );

		if ( $allow_all ) {
			$this->set_placeholder( __( '[Any]', 'automatewoo' ) );
		}

		$this->set_options( AW()->bookings_proxy()->get_booking_statuses() );
	}
}
