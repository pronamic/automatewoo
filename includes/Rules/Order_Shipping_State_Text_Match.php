<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Shipping_State_Text_Match
 */
class Order_Shipping_State_Text_Match extends Abstract_String {

	/** @var string */
	public $data_item = 'order';

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Shipping State - Text Match', 'automatewoo' );
	}

	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		$state = $order->get_shipping_state();
		$label = aw_get_state_name( $order->get_shipping_country(), $state );

		return $this->validate_string( false === $label ? $state : $label, $compare, $value );
	}
}
