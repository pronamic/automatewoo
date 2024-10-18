<?php

namespace AutomateWoo\Triggers\Utilities;

use AutomateWoo\Customer_Factory;
use AutomateWoo\Data_Layer;
use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Exceptions\InvalidValue;
use WC_Booking;
use WC_Order;

/**
 * Trait BookingDataLayer
 *
 * @since 5.3.0
 */
trait BookingDataLayer {

	/**
	 * Get the supplied data items for a booking.
	 *
	 * @return string[]
	 */
	protected function get_supplied_data_items_for_booking(): array {
		return [ DataTypes::BOOKING, DataTypes::CUSTOMER, DataTypes::PRODUCT, DataTypes::ORDER ];
	}

	/**
	 * Generate a booking data layer from a booking object.
	 *
	 * Includes booking, customer, booking product and order data types.
	 *
	 * @param WC_Booking $booking
	 *
	 * @return Data_Layer
	 *
	 * @throws InvalidValue If the booking's customer or booking is not found.
	 */
	protected function generate_booking_data_layer( WC_Booking $booking ): Data_Layer {
		// First try to retrieve customer from order.
		$order = $booking->get_order();
		// Bookings can be made without an order, so there's no need to log if the customer isn't found through the order.
		$log_error = false;
		$customer  = Customer_Factory::get_by_order( $order, true, $log_error );
		if ( ! $customer ) {
			// If that fails, retrieve customer from booking.
			$customer = Customer_Factory::get_by_user_id( $booking->get_customer_id() );
		}

		if ( ! $customer ) {
			throw InvalidValue::item_not_found( esc_html( DataTypes::CUSTOMER ) );
		}

		$product = $booking->get_product();
		if ( ! $product ) {
			throw InvalidValue::item_not_found( esc_html( DataTypes::PRODUCT ) );
		}

		return new Data_Layer(
			[
				DataTypes::BOOKING  => $booking,
				DataTypes::CUSTOMER => $customer,
				DataTypes::PRODUCT  => $product,
				DataTypes::ORDER    => $order ?: new WC_Order(),
			]
		);
	}
}
