<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Customer_Country
 */
class Variable_Customer_Country extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the customer's billing country.", 'automatewoo' );
	}

	/**
	 * @param Customer $customer
	 * @param array    $parameters
	 * @param Workflow $workflow
	 * @return string
	 */
	public function get_value( $customer, $parameters, $workflow ) {
		$country = $workflow->data_layer()->get_customer_country();

		if ( ! $country ) {
			return false;
		}

		return aw_get_country_name( $country );
	}
}
