<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Shipment_Tracking_Integration
 *
 * Handles integration with the WC Shipment Tracking extension.
 *
 * @since 4.6.0
 * @package AutomateWoo
 */
final class Shipment_Tracking_Integration {

	/**
	 * Get a specific shipment tracking field from an order.
	 *
	 * We get the field from the first tracking number only. i.e. we don't support multiple tracking numbers.
	 *
	 * Fields are: date_shipped, formatted_tracking_provider, tracking_number, formatted_tracking_link
	 *
	 * @param \WC_Order $order
	 * @param string    $field
	 *
	 * @return false|string
	 */
	public static function get_shipment_tracking_field( $order, $field ) {
		if ( ! class_exists( 'WC_Shipment_Tracking_Actions' ) ) {
			return false;
		}

		$tracking_items = \WC_Shipment_Tracking_Actions::get_instance()->get_tracking_items( $order->get_id(), true );

		if ( empty( $tracking_items ) || empty( $tracking_items[0][ $field ] ) ) {
			return false;
		}

		return $tracking_items[0][ $field ];
	}
}
