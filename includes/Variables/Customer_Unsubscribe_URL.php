<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Customer_Unsubscribe_URL
 * @since 3.9
 */
class Variable_Customer_Unsubscribe_URL extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays a URL that the customer can use to unsubscribe.', 'automatewoo' );
	}

	/**
	 * @param Customer $customer
	 * @param array    $parameters
	 * @param Workflow $workflow
	 * @return string
	 */
	public function get_value( $customer, $parameters, $workflow ) {
		return $workflow->get_unsubscribe_url( $customer );
	}
}
