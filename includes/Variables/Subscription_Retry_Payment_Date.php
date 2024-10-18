<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Subscription_Retry_Payment_Date
 */
class Variable_Subscription_Retry_Payment_Date extends Variable_Abstract_Datetime {


	/**
	 * Loads the admin details
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description  = __( 'Displays the subscription retry payment date in your website timezone.', 'automatewoo' );
		$this->description .= ' ' . $this->_desc_format_tip;
	}


	/**
	 * @param WC_Subscription $subscription  The WC_Subscription instance.
	 * @param array           $parameters   The parameters passed to the variable.
	 * @return string         The formatted date.
	 */
	public function get_value( $subscription, $parameters ) {
		return $this->format_datetime( $subscription->get_date( 'payment_retry', 'site' ), $parameters );
	}
}
