<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable;
use WC_Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Class BookingResource
 *
 * @since 5.3.0
 */
class BookingResource extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays any resources included in the booking.', 'automatewoo' );
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
		$resource = $booking->get_resource();
		if ( ! $resource ) {
			return '';
		}

		return $resource->get_title();
	}
}
