<?php

namespace AutomateWoo\Rules;

use WC_Subscription;

defined( 'ABSPATH' ) || exit;

/**
 * SubscriptionPaymentCount rule class.
 */
class SubscriptionPaymentCount extends Abstract_Number {

	/** @var string */
	public $data_item = 'subscription';

	/** @var bool */
	public $support_floats = false;

	/**
	 * Initializer
	 *
	 * @return void
	 */
	public function init() {
		$this->title = __( 'Subscription - Payment Count', 'automatewoo' );
	}


	/**
	 * @param WC_Subscription $subscription
	 * @param string          $compare
	 * @param mixed           $value
	 * @return bool
	 */
	public function validate( $subscription, $compare, $value ) {
		// Method changed in WCS 2.6
		$payment_count = is_callable( [ $subscription, 'get_payment_count' ] ) ? $subscription->get_payment_count() : $subscription->get_completed_payment_count();

		return $this->validate_number( $payment_count, $compare, $value );
	}
}
