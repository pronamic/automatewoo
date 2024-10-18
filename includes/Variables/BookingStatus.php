<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable;
use WC_Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Class BookingStatus
 *
 * @since 5.3.0
 */
class BookingStatus extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the status of the booking.', 'automatewoo' );
	}

	/**
	 * Get variable value.
	 *
	 * @param WC_Booking $booking
	 * @param array      $parameters
	 *
	 * @return string
	 */
	public function get_value( $booking, $parameters ) {
		return $booking->get_status();
	}
}
