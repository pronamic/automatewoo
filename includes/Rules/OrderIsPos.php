<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderIsPos rule class.
 */
class OrderIsPos extends Abstract_Bool {

	public $data_item = 'order';


	function init() {
		$this->title = __( "Order - Is POS", 'automatewoo' );
		$this->group = __( 'POS', 'automatewoo' );
	}


	/**
	 * @param WC_Order $order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {

		$is_pos = (bool) $order->get_meta( '_pos' );

		switch ( $value ) {
			case 'yes':
				return $is_pos;
				break;

			case 'no':
				return ! $is_pos;
				break;
		}
	}

}
