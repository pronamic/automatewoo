<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable_Abstract_Datetime;
use WC_Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Class BookingStartDate
 *
 * @since 5.4.0
 */
class BookingStartDate extends Variable_Abstract_Datetime {

	/**
	 * Load variable admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the booking start date in your website's timezone.", 'automatewoo' );
		parent::load_admin_details();
	}

	/**
	 * Get the variable value.
	 *
	 * @param WC_Booking $booking
	 * @param array      $parameters
	 *
	 * @return string
	 */
	public function get_value( $booking, $parameters ) {
		return $this->format_datetime( $booking->get_start( 'view', true ), $parameters );
	}
}
