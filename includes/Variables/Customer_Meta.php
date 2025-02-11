<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Customer_Meta
 */
class Variable_Customer_Meta extends Variable_Abstract_Meta {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the value of a customer's custom field. If the customer has an account, the value is pulled from the user meta table. If the customer is a guest, the guest meta table is used.", 'automatewoo' );
		parent::load_admin_details();
	}

	/**
	 * @param Customer $customer
	 * @param array    $parameters
	 * @param Workflow $workflow
	 * @return string
	 */
	public function get_value( $customer, $parameters, $workflow ) {
		if ( empty( $parameters['key'] ) ) {
			return '';
		}

		return (string) $customer->get_legacy_meta( $parameters['key'] );
	}
}
