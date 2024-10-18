<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable Subscription Total.
 *
 * @class Variable_Subscription_Total
 */
class Variable_Subscription_Total extends Variable_Abstract_Price {

	/**
	 * Load Admin Details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( "Displays the subscription's recurring total.", 'automatewoo' );
	}

	/**
	 * Get Value Method.
	 *
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 *
	 * @return string
	 */
	public function get_value( $subscription, $parameters ) {
		return parent::format_amount( $subscription->get_total(), $parameters, $subscription->get_currency() );
	}
}
