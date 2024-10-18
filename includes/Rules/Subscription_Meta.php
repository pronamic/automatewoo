<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Meta
 */
class Subscription_Meta extends Abstract_Meta {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * Init the rule
	 */
	public function init() {
		$this->title = __( 'Subscription - Custom Field', 'automatewoo' );
	}


	/**
	 * Validate the rule based on options set by a workflow
	 *
	 * @param \WC_Subscription $subscription
	 * @param string           $compare_type
	 * @param array            $value_data
	 *
	 * @return bool
	 */
	public function validate( $subscription, $compare_type, $value_data ) {

		$value_data = $this->prepare_value_data( $value_data );

		if ( ! is_array( $value_data ) ) {
			return false;
		}

		return $this->validate_meta( $subscription->get_meta( $value_data['key'] ), $compare_type, $value_data['value'] );
	}
}
