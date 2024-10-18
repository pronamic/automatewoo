<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Shipping_Provider
 */
class Variable_Order_Shipping_Provider extends Variable {


	function load_admin_details() {
		$this->description = sprintf(
			__( 'Displays the name of shipping provider as set with the <%s>WooCommerce Shipment Tracking<%s> extension.', 'automatewoo' ),
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
		return Shipment_Tracking_Integration::get_shipment_tracking_field( $order, 'formatted_tracking_provider' );
	}
}
