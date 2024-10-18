<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Subscription_Meta
 */
class Variable_Subscription_Meta extends Variable_Abstract_Meta {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( "Displays a subscription's custom field.", 'automatewoo' );
	}


	/**
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 *
	 * @return string
	 */
	public function get_value( $subscription, $parameters ) {
		if ( $parameters['key'] ) {
			return (string) $subscription->get_meta( $parameters['key'] );
		}
		return '';
	}
}
