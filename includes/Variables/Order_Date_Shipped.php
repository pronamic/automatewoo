<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Date_Shipped
 */
class Variable_Order_Date_Shipped extends Variable_Abstract_Datetime {


	function load_admin_details() {
		parent::load_admin_details();

		$this->description = sprintf(
			__( 'Displays the shipping date as set with the <%s>WooCommerce Shipment Tracking<%s> extension.', 'automatewoo' ),
			'a href="https://woocommerce.com/products/shipment-tracking/" target="_blank"',
			'/a'
		);
		$this->description .= ' ' . $this->_desc_format_tip;
	}


	/**
	 * Get variable value.
	 *
	 * @param \WC_Order $order
	 * @param array     $parameters
	 *
	 * @return string
	 */
	public function get_value( $order, $parameters ) {
		if ( empty( $parameters['format'] ) ) {
			// Before v4.6 this variable had no format param so to
			// smooth backwards compatibility use the WC date format
			$parameters['format'] = wc_date_format();
		}

		$timestamp = Shipment_Tracking_Integration::get_shipment_tracking_field( $order, 'date_shipped' );

		// Format as 'not GMT' so no timezone conversion happens
		return $this->format_datetime( $timestamp, $parameters, false );
	}
}
