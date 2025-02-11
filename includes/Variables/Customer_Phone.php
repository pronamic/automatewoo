<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Customer_Phone
 */
class Variable_Customer_Phone extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the customer's billing phone.", 'automatewoo' );
	}

	/**
	 * @param Customer $customer
	 * @param array    $parameters
	 * @param Workflow $workflow
	 * @return string
	 */
	public function get_value( $customer, $parameters, $workflow ) {
		return $workflow->data_layer()->get_customer_phone();
	}
}
