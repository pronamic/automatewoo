<?php
namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Trigger_Order_Refunded_Manual
 *
 * @since 5.6.2
 */
class Trigger_Order_Refunded_Manual extends Trigger_Order_Refunded {
	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Order Refunded Manually', 'automatewoo' );
		$this->description = __( 'Fires when an order is refunded manually. This trigger does not fire when an order is refunded automatically.', 'automatewoo' );
	}

	/**
	 * Support manual refunds only?
	 *
	 * @return bool
	 */
	public function support_manual_refund_only() {
		return true;
	}
}
