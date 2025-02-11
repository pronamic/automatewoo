<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Customer_Generate_Coupon
 */
class Variable_Customer_Generate_Coupon extends Variable_Abstract_Generate_Coupon {

	/**
	 * @param Customer $customer
	 * @param array    $parameters
	 * @param Workflow $workflow
	 * @return string
	 */
	public function get_value( $customer, $parameters, $workflow ) {
		return $this->generate_coupon( $customer->get_email(), $parameters, $workflow );
	}
}
