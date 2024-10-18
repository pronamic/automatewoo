<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subscription Admin URL Variable
 *
 * @class Variable_Subscription_Admin_Url
 */
class Variable_Subscription_Admin_Url extends Variable {

	/**
	 * Load admin details
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the admin URL of the subscription.', 'automatewoo' );
	}

	/**
	 * Get value method.
	 *
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 *
	 * @return mixed
	 */
	public function get_value( $subscription, $parameters ) {
		return $subscription->get_edit_order_url();
	}
}
