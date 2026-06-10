<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variable_Subscription_Billing_Email class.
 *
 * @since 6.4.0
 *
 * @class Variable_Subscription_Billing_Email
 */
class Variable_Subscription_Billing_Email extends Variable {

	/**
	 * Method to set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the billing email for the subscription.', 'automatewoo' );
	}

	/**
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 *
	 * @return string
	 */
	public function get_value( $subscription, $parameters ) {
		return $subscription->get_billing_email();
	}
}
