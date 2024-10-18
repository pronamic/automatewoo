<?php

namespace AutomateWoo\Rules;

use WC_Subscription;

defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription_Run_Count
 *
 * @version 5.0.0
 * @package AutomateWoo\Rules
 */
class Subscription_Run_Count extends Abstract_Number {

	/**
	 * The data type used by the rule.
	 *
	 * @var string
	 */
	public $data_item = 'subscription';

	/**
	 * Set whether the rule supports floats or only integers.
	 *
	 * @var bool
	 */
	public $support_floats = false;

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Workflow - Run Count For Subscription', 'automatewoo' );
	}

	/**
	 * Validate the rule.
	 *
	 * @param WC_Subscription $subscription
	 * @param string          $compare
	 * @param string          $value
	 *
	 * @return bool
	 */
	public function validate( $subscription, $compare, $value ) {
		$workflow = $this->get_workflow();

		if ( ! $workflow ) {
			return false;
		}

		return $this->validate_number( $workflow->get_run_count_for_subscription( $subscription ), $compare, $value );
	}
}
