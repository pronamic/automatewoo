<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Triggers\Utilities\SubscriptionGroup;
use WC_Order;

/**
 * Trigger_Subscription_Note_Added class.
 *
 * @since 4.5
 */
class Trigger_Subscription_Note_Added extends Trigger_Order_Note_Added {

	use SubscriptionGroup;

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ DataTypes::SUBSCRIPTION, DataTypes::ORDER_NOTE, DataTypes::CUSTOMER ];

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Subscription Note Added', 'automatewoo' );
		$this->description = __( 'Fires when a note is added to a subscription. This includes private notes and notes to the customer. These notes appear on the right of the subscription edit screen.', 'automatewoo' );
	}

	/**
	 * Get order types to target in the order note trigger.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	protected function get_target_order_types(): array {
		return [ 'shop_subscription' ];
	}

	/**
	 * Handle when an order note is added.
	 *
	 * @since 5.2.0
	 *
	 * @param Order_Note $order_note
	 * @param WC_Order   $subscription
	 */
	protected function handle_order_note_added( Order_Note $order_note, WC_Order $subscription ) {

		// Reset status transition to prevent it from being triggered again if we run an action to update the subscription.
		// See: https://github.com/Automattic/woocommerce-subscriptions-core/issues/549
		// This code can be removed again if the behaviour is changed.
		$subscription->status_transition = false;

		$this->maybe_run(
			[
				'customer'     => Customer_Factory::get_by_user_id( $subscription->get_user_id() ),
				'subscription' => $subscription,
				'order_note'   => $order_note,
			]
		);
	}
}
