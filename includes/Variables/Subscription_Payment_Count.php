<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable;

defined( 'ABSPATH' ) || exit;

/**
 * Subscription payment count variable.
 *
 * @since 4.9.0
 */
class Subscription_Payment_Count extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the subscription payment count.', 'automatewoo' );
	}

	/**
	 * Get value.
	 *
	 * @param \WC_Subscription $subscription
	 *
	 * @return string
	 */
	public function get_value( $subscription ) {
		return (string) is_callable( [ $subscription, 'get_payment_count' ] ) ? $subscription->get_payment_count() : $subscription->get_completed_payment_count();
	}
}
