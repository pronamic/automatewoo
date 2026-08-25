<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Shipping_Phone_Text_Match
 */
class Subscription_Shipping_Phone_Text_Match extends Order_Shipping_Phone_Text_Match {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Subscription - Shipping Phone - Text Match', 'automatewoo' );
	}
}
