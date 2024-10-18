<?php

namespace AutomateWoo;

use AutomateWoo\Frontend_Endpoints\Login_Redirect;

defined( 'ABSPATH' ) || exit;

/**
 * Variable_Subscription_Change_Payment_Method_Url class.
 *
 * @since 4.4.3
 */
class Variable_Subscription_Change_Payment_Method_Url extends Variable {

	/**
	 * Load admin props.
	 */
	public function load_admin_details() {
		$this->description = __( 'Shows a URL to the subscription add/change payment method page.', 'automatewoo' );
	}

	/**
	 * Get the variable's value.
	 *
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 *
	 * @return string
	 */
	public function get_value( $subscription, $parameters ) {
		return ( new Login_Redirect() )->get_login_redirect_url( $subscription->get_change_payment_method_url() );
	}
}
