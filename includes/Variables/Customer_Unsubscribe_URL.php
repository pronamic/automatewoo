<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Customer_Unsubscribe_URL
 * @since 3.9
 */
class Variable_Customer_Unsubscribe_URL extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays a URL that the customer can use to unsubscribe.", 'automatewoo');
	}


	/**
	 * @param $customer Customer
	 * @param $parameters array
	 * @param $workflow Workflow
	 * @return string
	 */
	function get_value( $customer, $parameters, $workflow ) {
		return $workflow->get_unsubscribe_url( $customer );
	}

}
