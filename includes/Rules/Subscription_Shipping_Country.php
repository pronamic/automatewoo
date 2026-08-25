<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Shipping_Country
 */
class Subscription_Shipping_Country extends OrderShippingCountry {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Subscription - Shipping Country', 'automatewoo' );
	}
}
