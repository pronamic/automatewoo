<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Order_Shipping_State() class.
 *
 * @since 4.4.2
 *
 * @class Variable_Order_Shipping_State
 */
class Variable_Order_Shipping_State extends Variable_Customer_State {

	/**
	 * Load description and parameters for variable in admin screen.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the shipping state for the order.', 'automatewoo' );
	}


	/**
	 * Method: get_value() - returns the state name or abbreviation.
	 *
	 * @param \WC_Order $order
	 * @param array     $parameters
	 * @param Workflow  $workflow
	 *
	 * @return string
	 */
	public function get_value( $order, $parameters, $workflow ) {
		$format  = isset( $parameters['format'] ) ? $parameters['format'] : 'full';
		$state   = $order->get_shipping_state();
		$country = $order->get_shipping_country();
		$return  = null;

		switch ( $format ) {
			case 'full':
				$return = aw_get_state_name( $country, $state );
				break;
			case 'abbreviation':
				$return = $state;
				break;
		}

		return $return;
	}
}
