<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable;
use WC_Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Class BookingId
 *
 * @since 5.3.0
 */
class BookingId extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the booking's unique ID.", 'automatewoo' );
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
		return (string) $booking->get_id();
	}
}
