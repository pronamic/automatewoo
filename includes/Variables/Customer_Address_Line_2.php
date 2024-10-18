<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Customer_Address_Line_2 class.
 *
 * @since 4.5.0
 *
 * @class Variable_Customer_Address_Line_2
 */
class Variable_Customer_Address_Line_2 extends Variable {


	/**
	 * Load description on admin screen.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the second line of the customer's address.", 'automatewoo' );
	}


	/**
	 * Method: get_value() - get and return the second line of the customer's address.
	 *
	 * @param Customer $customer
	 * @param array    $parameters
	 * @param Workflow $workflow
	 *
	 * @return string
	 */
	public function get_value( $customer, $parameters, $workflow ) {
		return $workflow->data_layer()->get_customer_address_2();
	}
}
