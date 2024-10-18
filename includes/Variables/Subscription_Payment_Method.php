<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Subscription_Payment_Method
 */
class Variable_Subscription_Payment_Method extends Variable {

	/**
	 * Method to set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the payment method of the subscription.', 'automatewoo' );
	}


	/**
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 * @return string
	 */
	public function get_value( $subscription, $parameters ) {
		return $subscription->get_payment_method_to_display();
	}
}
