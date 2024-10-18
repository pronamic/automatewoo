<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Customer_Generate_Coupon
 */
class Variable_Customer_Generate_Coupon extends Variable_Abstract_Generate_Coupon {

	/**
	 * @param $customer Customer
	 * @param $parameters array
	 * @param $workflow Workflow
	 * @return string
	 */
	function get_value( $customer, $parameters, $workflow ) {
		return $this->generate_coupon( $customer->get_email(), $parameters, $workflow );
	}

}
