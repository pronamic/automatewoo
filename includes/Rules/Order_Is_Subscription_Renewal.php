<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Is_Subscription_Renewal
 * @since 2.9
 */
class Order_Is_Subscription_Renewal extends Abstract_Bool {

	public $data_item = 'order';


	function init() {
		$this->title = __( "Order - Is Subscription Renewal", 'automatewoo' );
	}


	/**
	 * @param $order \WC_Order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {

		$is_renewal = wcs_order_contains_renewal( $order );

		switch ( $value ) {
			case 'yes':
				return $is_renewal;
				break;

			case 'no':
				return ! $is_renewal;
				break;
		}
	}

}
