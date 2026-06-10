<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Order_Billing_State() class.
 *
 * @since 6.4.0
 *
 * @class Variable_Order_Billing_State
 */
class Variable_Order_Billing_State extends Variable_Customer_State {

	/**
	 * Load description and parameters for variable in admin screen.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the billing state for the order.', 'automatewoo' );
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
		$state   = $order->get_billing_state();
		$country = $order->get_billing_country();
		$return  = null;

		switch ( $format ) {
			case 'abbreviation':
				$return = $state;
				break;
			case 'full':
			default:
				$return = aw_get_state_name( $country, $state );
				break;
		}

		return $return;
	}
}
