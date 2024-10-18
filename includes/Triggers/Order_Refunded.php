<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Order_Refunded
 */
class Trigger_Order_Refunded extends Trigger {
	/**
	 * Set data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ DataTypes::ORDER, DataTypes::REFUND, DataTypes::CUSTOMER ];

	/**
	 * Target transition status.
	 *
	 * @var string|false
	 */
	public $target_status = 'refunded';

	/**
	 * Method to set title, group, description and other admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Order Refunded', 'automatewoo' );
		$this->description = __( 'Fires when an order is refunded, both for partial and full refunds.', 'automatewoo' );
		$this->group       = __( 'Orders', 'automatewoo' );
	}

	/**
	 * Support manual refunds only?
	 *
	 * @return bool
	 */
	public function support_manual_refund_only() {
		return false;
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'woocommerce_refund_created', array( $this, 'catch_hooks' ) );
	}

	/**
	 * Catches the action and calls the maybe_run() method.
	 *
	 * @param int $refund_id Refund ID.
	 */
	public function catch_hooks( $refund_id ) {
		$refund = wc_get_order( $refund_id );
		if ( ! $refund ) {
			return;
		}

		// Bail if trigger supports manual refunds only and the refund is automatic.
		if ( $this->support_manual_refund_only() && $refund->get_refunded_payment() ) {
			return;
		}

		$order = wc_get_order( $refund->get_parent_id() );
		if ( ! $order ) {
			return;
		}

		$this->maybe_run(
			[
				DataTypes::ORDER    => $order,
				DataTypes::REFUND   => $refund,
				DataTypes::CUSTOMER => Customer_Factory::get_by_order( $order ),
			]
		);
	}
}
