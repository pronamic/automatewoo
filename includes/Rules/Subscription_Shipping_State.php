<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Shipping_State
 */
class Subscription_Shipping_State extends Order_Shipping_State {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Subscription - Shipping State', 'automatewoo' );
	}
}
