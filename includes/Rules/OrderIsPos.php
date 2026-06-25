<?php

namespace AutomateWoo\Rules;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderIsPos rule class.
 */
class OrderIsPos extends Abstract_Bool {

	/**
	 * @var string
	 */
	public $data_item = 'order';


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Is POS', 'automatewoo' );
		$this->group = __( 'POS', 'automatewoo' );
	}


	/**
	 * @param WC_Order $order
	 * @param string   $compare
	 * @param mixed    $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {

		$is_pos = (bool) $order->get_meta( '_pos' );

		switch ( $value ) {
			case 'yes':
				return $is_pos;

			case 'no':
				return ! $is_pos;
		}
	}
}
