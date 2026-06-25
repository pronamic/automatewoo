<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Is_Subscription_Parent
 * @since 4.3
 */
class Order_Is_Subscription_Parent extends Abstract_Bool {

	/** @var string */
	public $data_item = 'order';


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Is Subscription Parent', 'automatewoo' );
	}


	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		$is_parent = wcs_order_contains_subscription( $order, 'parent' );
		return $value === 'yes' ? $is_parent : ! $is_parent;
	}
}
