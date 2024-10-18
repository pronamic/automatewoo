<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable;
use WC_Booking;
use WC_Product_Booking_Person_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Class BookingPersonCount
 *
 * @since 5.4.0
 */
class BookingPersons extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Shows the number of persons for the booking (if applicable). If the booking has multiple person types the number of persons will be listed for each type.', 'automatewoo' );
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
		$person_count = $booking->get_persons_total();

		if ( $person_count < 1 ) {
			// Booking does not have persons.
			return '';
		}

		$booking_product = $booking->get_product();
		if ( ! $booking_product->has_person_types() ) {
			// Booking has simple person count.
			return $person_count;
		}

		// Booking has various types of person.
		$booking_persons = $booking->get_persons();
		$persons         = [];
		foreach ( $booking_persons as $person_id => $count ) {
			$person_type = new WC_Product_Booking_Person_Type( $person_id );
			$person_name = $person_type->get_name();
			$persons[]   = "$person_name: $count";
		}

		return implode( ', ', $persons );
	}
}
