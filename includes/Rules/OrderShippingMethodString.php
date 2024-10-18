<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderShippingMethodString rule class.
 */
class OrderShippingMethodString extends Abstract_String {

	public $data_item = 'order';


	function init() {
		$this->title = __( 'Order - Shipping Method - Text Match', 'automatewoo' );
	}


	/**
	 * @param WC_Order $order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {
		return $this->validate_string( $order->get_shipping_method(), $compare, $value );
	}

}
