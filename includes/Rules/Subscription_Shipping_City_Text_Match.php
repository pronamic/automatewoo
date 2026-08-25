<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Shipping_City_Text_Match
 */
class Subscription_Shipping_City_Text_Match extends Order_Shipping_City_Text_Match {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Subscription - Shipping City - Text Match', 'automatewoo' );
	}
}
