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
		$this->add_parameter_select_field(
			'format',
			__( 'Choose whether to display the booking status slug or label.', 'automatewoo' ),
			[
				''      => __( 'Slug', 'automatewoo' ),
				'label' => __( 'Label', 'automatewoo' ),
			]
		);
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
		$status = $booking->get_status();

		if ( isset( $parameters['format'] ) && 'label' === $parameters['format'] ) {
			$statuses = \AW()->bookings_proxy()->get_booking_statuses();
			return $statuses[ $status ] ?? $status;
		}

		return $status;
	}
}
