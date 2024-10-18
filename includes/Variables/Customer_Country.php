<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Customer_Country
 */
class Variable_Customer_Country extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the customer's billing country.", 'automatewoo');
	}


	/**
	 * @param $customer Customer
	 * @param $parameters array
	 * @param $workflow Workflow
	 * @return string
	 */
	function get_value( $customer, $parameters, $workflow ) {
		$country = $workflow->data_layer()->get_customer_country();

		if ( ! $country ) {
			return false;
		}

		return aw_get_country_name( $country );
	}

}
