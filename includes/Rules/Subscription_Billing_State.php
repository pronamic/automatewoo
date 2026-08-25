<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Billing_State
 */
class Subscription_Billing_State extends Order_Billing_State {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Subscription - Billing State', 'automatewoo' );
	}
}
