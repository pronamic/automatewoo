<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Order_Shipping_Country class.
 *
 * @since 4.4.2
 *
 * @class Variable_Order_Shipping_Country
 */
class Variable_Order_Shipping_Country extends Variable {

	/**
	 * Load description and parameters for variable in admin screen.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the shipping country for the order.', 'automatewoo' );

		$this->add_parameter_select_field(
			'format',
			__( 'Choose whether to display the abbreviation or full name of the country.', 'automatewoo' ),
			[
				''             => __( 'Full', 'automatewoo' ),
				'abbreviation' => __( 'Abbreviation', 'automatewoo' ),
			],
			false
		);
	}


	/**
	 * Method: get_value() - returns full country name.
	 *
	 * @param \WC_Order $order
	 * @param array     $parameters
	 *
	 * @return string $return
	 */
	public function get_value( $order, $parameters ) {

		$format = isset( $parameters['format'] ) ? $parameters['format'] : 'full';

		$return = null;

		switch ( $format ) {
			case 'full':
				$return = aw_get_country_name( $order->get_shipping_country() );
				break;
			case 'abbreviation':
				$return = $order->get_shipping_country();
				break;
		}

		return $return;
	}
}
