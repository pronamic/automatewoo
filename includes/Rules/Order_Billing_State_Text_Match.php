<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Billing_State_Text_Match
 */
class Order_Billing_State_Text_Match extends Abstract_String {

	/** @var string */
	public $data_item = 'order';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Billing State - Text Match', 'automatewoo' );
	}

	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		$state = $order->get_billing_state();
		$label = aw_get_state_name( $order->get_billing_country(), $state );

		return $this->validate_string( false === $label ? $state : $label, $compare, $value );
	}
}
