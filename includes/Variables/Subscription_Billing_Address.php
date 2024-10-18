<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Subscription_Billing_Address
 */
class Variable_Subscription_Billing_Address extends Variable {

	/**
	 * Method to set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the formatted billing address for the subscription.', 'automatewoo' );
	}


	/**
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 * @return string
	 */
	public function get_value( $subscription, $parameters ) {
		return $subscription->get_formatted_billing_address();
	}
}
