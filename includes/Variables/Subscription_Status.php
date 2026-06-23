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
		$this->add_parameter_select_field(
			'format',
			__( 'Choose whether to display the subscription status slug or label.', 'automatewoo' ),
			[
				''      => __( 'Slug', 'automatewoo' ),
				'label' => __( 'Label', 'automatewoo' ),
			]
		);
	}

	/**
	 * @param \WC_Subscription $subscription
	 * @param array            $parameters
	 * @return string
	 */
	public function get_value( $subscription, $parameters ) {
		$status = $subscription->get_status();

		if ( isset( $parameters['format'] ) && 'label' === $parameters['format'] ) {
			$statuses = Subscription_Workflow_Helper::get_subscription_statuses();
			return $statuses[ 'wc-' . $status ] ?? $status;
		}

		return $status;
	}
}
