<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription_Coupon_Count
 *
 * @since   4.5.0
 * @package AutomateWoo\Rules
 */
class Subscription_Coupon_Count extends Order_Coupon_Count {

	/**
	 * Data item for the rule.
	 *
	 * @var string
	 */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Subscription - Coupon Count', 'automatewoo' );
	}
}
