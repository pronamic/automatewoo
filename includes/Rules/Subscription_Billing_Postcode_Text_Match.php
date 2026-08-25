<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Billing_Postcode_Text_Match
 */
class Subscription_Billing_Postcode_Text_Match extends Order_Billing_Postcode_Text_Match {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Subscription - Billing Postcode - Text Match', 'automatewoo' );
	}
}
