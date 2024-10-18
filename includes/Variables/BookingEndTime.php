<?php

namespace AutomateWoo\Variables;

use AutomateWoo\DateTime;
use WC_Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Class BookingEndTime
 *
 * @since 5.4.0
 */
class BookingEndTime extends AbstractBookingTime {

	/**
	 * Load variable admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the booking end time in your website's timezone. Nothing will be displayed for all-day bookings.", 'automatewoo' );
	}

	/**
	 * Get the target booking datetime value for the variable.
	 *
	 * @param WC_Booking $booking
	 *
	 * @return DateTime|null The variable's target datetime value in the site's local timezone.
	 */
	protected function get_target_datetime_value( WC_Booking $booking ) {
		$datetime = aw_normalize_date( $booking->get_end( 'view', true ) );
		return $datetime ? $datetime : null;
	}
}
