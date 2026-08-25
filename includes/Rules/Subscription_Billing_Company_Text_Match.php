<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Billing_Company_Text_Match
 */
class Subscription_Billing_Company_Text_Match extends Order_Billing_Company_Text_Match {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Subscription - Billing Company - Text Match', 'automatewoo' );
	}
}
