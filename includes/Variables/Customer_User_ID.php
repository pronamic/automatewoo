<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Customer_User_ID
 */
class Variable_Customer_User_ID extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the customer's user ID.", 'automatewoo');
	}


	/**
	 * @param $customer Customer
	 * @param $parameters array
	 * @param $workflow Workflow
	 * @return string
	 */
	function get_value( $customer, $parameters, $workflow ) {
		return $customer->get_user_id();
	}

}
