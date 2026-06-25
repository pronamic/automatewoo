<?php

namespace AutomateWoo\Rules;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderHasCrossSells rule class.
 */
class OrderHasCrossSells extends Abstract_Bool {

	/**
	 * @var string
	 */
	public $data_item = 'order';


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Has Cross-Sells Available', 'automatewoo' );
	}


	/**
	 * @param WC_Order $order
	 * @param string   $compare
	 * @param mixed    $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {

		$cross_sells = aw_get_order_cross_sells( $order );

		switch ( $value ) {
			case 'yes':
				return ! empty( $cross_sells );

			case 'no':
				return empty( $cross_sells );
		}
	}
}
