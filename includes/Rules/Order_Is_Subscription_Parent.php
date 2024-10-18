<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Is_Subscription_Parent
 * @since 4.3
 */
class Order_Is_Subscription_Parent extends Abstract_Bool {

	public $data_item = 'order';


	function init() {
		$this->title = __( "Order - Is Subscription Parent", 'automatewoo' );
	}


	/**
	 * @param $order \WC_Order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {
		$is_parent = wcs_order_contains_subscription( $order, 'parent' );
		return $value === 'yes' ? $is_parent : ! $is_parent;
	}

}
