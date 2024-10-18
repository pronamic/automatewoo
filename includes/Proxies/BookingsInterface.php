<?php

namespace AutomateWoo\Proxies;

use AutomateWoo\Exceptions\InvalidIntegration;
use AutomateWoo\Exceptions\InvalidValue;
use WC_Booking;

/**
 * Proxy for the WooCommerce bookings extension.
 *
 * @since 5.3.0
 */
interface BookingsInterface {

	/**
	 * Get a booking by ID.
	 *
	 * @param int $id
	 *
	 * @return WC_Booking
	 *
	 * @throws InvalidValue If booking not found.
	 * @throws InvalidIntegration If bookings plugin not active.
	 */
	public function get_booking( int $id ): WC_Booking;

	/**
	 * Return a list of supported booking status values & labels.
	 *
	 * @return Array Array of valid status values, in slug => label form.
	 */
	public function get_booking_statuses(): array;
}
