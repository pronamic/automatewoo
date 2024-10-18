<?php

namespace AutomateWoo\Proxies;

use AutomateWoo\Exceptions\InvalidValue;
use AutomateWoo\Exceptions\InvalidIntegration;
use AutomateWoo\Traits\IntegrationValidator;
use WC_Booking;
use WC_Booking_Data_Store;

defined( 'ABSPATH' ) || exit;

/**
 * Proxy for the WooCommerce bookings integration.
 *
 * @since 5.3.0
 */
class Bookings implements BookingsInterface {

	use IntegrationValidator;

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
	public function get_booking( int $id ): WC_Booking {
		$this->validate_bookings_integration();

		$booking = get_wc_booking( $id );
		if ( ! $booking instanceof WC_Booking ) {
			throw InvalidValue::item_not_found();
		}

		return $booking;
	}

	/**
	 * Get booking ids by filters.
	 *
	 * The 'status' filter defaults to use all booking statuses excluding 'trash'.
	 *
	 * @see WC_Booking_Data_Store::get_booking_ids_by (wrapped method)
	 *
	 * @param array $filters Filters for the query.
	 * @param int   $limit  The query limit.
	 * @param int   $offset The query offset.
	 *
	 * @return int[]
	 *
	 * @throws InvalidIntegration If bookings plugin not active.
	 */
	public function get_booking_ids_by( array $filters = [], int $limit = -1, int $offset = 0 ): array {
		$this->validate_bookings_integration();

		$filters['offset'] = $offset;
		$filters['limit']  = $limit;
		$filters           = array_merge(
			[
				// Set query statuses so trashed booking aren't included
				'status' => array_keys( $this->get_booking_statuses() ),
			],
			$filters
		);

		return WC_Booking_Data_Store::get_booking_ids_by( $filters );
	}

	/**
	 * Get the most recent booking.
	 *
	 * @return WC_Booking
	 *
	 * @throws InvalidIntegration If bookings plugin not active.
	 * @throws InvalidValue If booking not found.
	 */
	public function get_most_recent_booking(): WC_Booking {
		$this->validate_bookings_integration();

		$ids = $this->get_booking_ids_by( [], 1 );
		if ( empty( $ids ) ) {
			throw InvalidValue::item_not_found();
		}
		return $this->get_booking( $ids[0] );
	}

	/**
	 * Return a list of supported booking status values & labels.
	 *
	 * @return array Array of valid status values, in slug => label form.
	 */
	public function get_booking_statuses(): array {
		// Hard-coding these for now.
		// We could call `get_wc_booking_statuses( $context )`, but we would need to hard-code
		// various values for $context, and then remove duplicates.
		// Simpler to just hard-code the status values directly.
		return [
			'unpaid'               => __( 'Unpaid', 'automatewoo' ),
			'pending-confirmation' => __( 'Pending confirmation', 'automatewoo' ),
			'confirmed'            => __( 'Confirmed', 'automatewoo' ),
			'paid'                 => __( 'Paid', 'automatewoo' ),
			'complete'             => __( 'Complete', 'automatewoo' ),
			'in-cart'              => __( 'In cart', 'automatewoo' ),
			'cancelled'            => __( 'Cancelled', 'automatewoo' ),
		];
	}

	/**
	 * Get a list of draft booking statuses.
	 *
	 * @since 5.4.0
	 *
	 * @return string[]
	 */
	public function get_draft_booking_statuses(): array {
		return [ 'in-cart', 'was-in-cart' ];
	}
}
