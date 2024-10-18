<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Tracking_Number
 */
class Variable_Order_Tracking_Number extends Variable {


	function load_admin_details() {
		$this->description = sprintf(
			__( 'Displays the tracking number as set with the <%s>WooCommerce Shipment Tracking<%s> extension.', 'automatewoo' ),
			'a href="https://woocommerce.com/products/shipment-tracking/" target="_blank"',
			'/a'
		);
	}

	/**
	 * Get variable value.
	 *
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public function get_value( $order ) {
		return Shipment_Tracking_Integration::get_shipment_tracking_field( $order, 'tracking_number' );
	}
}
