<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderHasCrossSells rule class.
 */
class OrderHasCrossSells extends Abstract_Bool {

	public $data_item = 'order';


	function init() {
		$this->title = __( 'Order - Has Cross-Sells Available', 'automatewoo' );
	}


	/**
	 * @param WC_Order $order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {

		$cross_sells = aw_get_order_cross_sells( $order );

		switch ( $value ) {
			case 'yes':
				return ! empty( $cross_sells );
				break;

			case 'no':
				return empty( $cross_sells );
				break;
		}
	}

}
