<?php

namespace AutomateWoo\Variables;

use AutomateWoo\DateTime;
use WC_Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractBookingTime
 *
 * @since 5.4.0
 */
abstract class AbstractBookingTime extends AbstractTime {

	/**
	 * Get the target booking datetime value for the variable.
	 *
	 * @param WC_Booking $booking
	 *
	 * @return DateTime|null The variable's target datetime value in the site's local timezone.
	 */
	abstract protected function get_target_datetime_value( WC_Booking $booking );

	/**
	 * Get the variable value.
	 *
	 * If booking is "all-day" no time will be returned.
	 *
	 * @param WC_Booking $booking
	 * @param array      $parameters
	 *
	 * @return string
	 */
	public function get_value( $booking, $parameters ) {
		if ( $booking->is_all_day() ) {
			// All-day bookings have no time.
			// Returning '' here lets users use the 'fallback' parameter for all-day bookings.
			return '';
		}

		$datetime = $this->get_target_datetime_value( $booking );
		if ( ! $datetime ) {
			return '';
		}

		return $this->format_value_from_local_tz( $datetime );
	}
}
