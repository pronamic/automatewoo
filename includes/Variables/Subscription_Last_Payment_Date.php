<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Subscription_Last_Payment_Date
 */
class Variable_Subscription_Last_Payment_Date extends Variable_Abstract_Datetime {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description  = __( 'Displays the date of the most recent payment for the subscription.', 'automatewoo' );
		$this->description .= ' ' . $this->_desc_format_tip;
	}

	/**
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 * @return string
	 */
	public function get_value( $subscription, $parameters ) {
		return $this->format_datetime( $subscription->get_date( 'last_payment', 'site' ), $parameters );
	}
}
