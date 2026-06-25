<?php

namespace AutomateWoo\Rules;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderShippingMethodString rule class.
 */
class OrderShippingMethodString extends Abstract_String {

	/** @var string */
	public $data_item = 'order';


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Shipping Method - Text Match', 'automatewoo' );
	}


	/**
	 * @param WC_Order $order
	 * @param string   $compare
	 * @param mixed    $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		return $this->validate_string( $order->get_shipping_method(), $compare, $value );
	}
}
