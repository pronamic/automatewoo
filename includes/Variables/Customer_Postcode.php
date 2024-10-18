<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Customer_Postcode
 */
class Variable_Customer_Postcode extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the customer's billing postcode.", 'automatewoo');
	}


	/**
	 * @param $customer Customer
	 * @param $parameters array
	 * @param $workflow Workflow
	 * @return string
	 */
	function get_value( $customer, $parameters, $workflow ) {
		return $workflow->data_layer()->get_customer_postcode();
	}

}
