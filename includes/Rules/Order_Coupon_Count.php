<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Coupon_Count
 * @since 4.2
 */
class Order_Coupon_Count extends Abstract_Number {

	/** @var string */
	public $data_item = 'order';

	/** @var bool */
	public $support_floats = false;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Coupon Count', 'automatewoo' );
	}


	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		return $this->validate_number( count( $order->get_coupon_codes() ), $compare, $value );
	}
}
