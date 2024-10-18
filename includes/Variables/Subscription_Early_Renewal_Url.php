<?php

namespace AutomateWoo;

use AutomateWoo\Frontend_Endpoints\Login_Redirect;

defined( 'ABSPATH' ) || exit;

/**
 * Subscription Early Renewal URL Variable.
 *
 * @since 4.5.0
 *
 * @class Variable_Subscription_Early_Renewal_Url
 */
class Variable_Subscription_Early_Renewal_Url extends Variable {

	/**
	 * Load admin description.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the early renewal URL for the subscription.', 'automatewoo' );
	}

	/**
	 * Get Value method.
	 *
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 *
	 * @return string
	 */
	public function get_value( $subscription, $parameters ) {

		$user_id = $subscription->get_user_id();

		if ( wcs_can_user_renew_early( $subscription, $user_id ) ) {
			return ( new Login_Redirect() )->get_login_redirect_url( wcs_get_early_renewal_url( $subscription ) );
		} else {
			return false;
		}
	}
}
