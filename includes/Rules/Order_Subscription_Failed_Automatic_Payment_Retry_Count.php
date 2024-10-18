<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Subscription_Failed_Automatic_Payment_Retry_Count
 */
class Order_Subscription_Failed_Automatic_Payment_Retry_Count extends Abstract_Number {

	/** @var string */
	public $data_item = 'order';

	/** @var bool */
	public $support_floats = false;


	/**
	 * @return void
	 */
	public function init() {
		$this->title = __( 'Order - Subscription Failed Automatic Payment Retry Count', 'automatewoo' );
	}


	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		$failed_retries = \WCS_Retry_Manager::store()->get_retries(
			[
				'status'   => 'failed',
				'order_id' => $order->get_id(),
			],
			'ids'
		);

		return $this->validate_number( count( $failed_retries ), $compare, $value );
	}
}
