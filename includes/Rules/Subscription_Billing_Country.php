<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Billing_Country
 */
class Subscription_Billing_Country extends Order_Billing_Country {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Subscription - Billing Country', 'automatewoo' );
	}
}
