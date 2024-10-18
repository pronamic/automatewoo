<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Subscription_Status
 */
class Variable_Subscription_Status extends Variable {


	/**
	 * Method to set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the formatted status of the subscription.', 'automatewoo' );
	}


	/**
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 * @return string
	 */
	public function get_value( $subscription, $parameters ) {
		return $subscription->get_status();
	}
}
