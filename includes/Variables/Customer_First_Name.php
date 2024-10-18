<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Customer_First_Name
 */
class Variable_Customer_First_Name extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the customer's first name.", 'automatewoo');
	}


	/**
	 * @param $customer Customer
	 * @param $parameters array
	 * @param $workflow Workflow
	 * @return string
	 */
	function get_value( $customer, $parameters, $workflow ) {
		return $workflow->data_layer()->get_customer_first_name();
	}

}
