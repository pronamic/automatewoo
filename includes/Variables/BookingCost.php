<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable;
use AutomateWoo\Variable_Abstract_Price;
use WC_Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Class BookingCost
 *
 * @since 5.3.0
 */
class BookingCost extends Variable_Abstract_Price {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the cost of the booking.', 'automatewoo' );
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
		return parent::format_amount( $booking->get_cost(), $parameters );
	}
}
