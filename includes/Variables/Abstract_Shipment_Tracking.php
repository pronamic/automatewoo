<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

aw_deprecated_class( Variable_Abstract_Shipment_Tracking::class, '5.2.0', Shipment_Tracking_Integration::class );

/**
 * Variable_Abstract_Shipment_Tracking class.
 *
 * @deprecated Use \AutomateWoo\Shipment_Tracking_Integration::get_shipment_tracking_field instead.
 */
abstract class Variable_Abstract_Shipment_Tracking extends Variable {

	/**
	 * Gets the first shipment tracking array
	 *
	 * @param \WC_Order $order
	 * @param string    $field
	 * @return false|string
	 */
	public function get_shipment_tracking_field( $order, $field ) {
		return Shipment_Tracking_Integration::get_shipment_tracking_field( $order, $field );
	}
}
