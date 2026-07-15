<?php

namespace AutomateWoo\Async_Events;

use WC_Order;
use WC_Subscription;

defined( 'ABSPATH' ) || exit;

/**
 * Async event for when a subscription item is switched.
 *
 * @since 6.4.0
 * @package AutomateWoo
 */
class Subscription_Switched extends Abstract_Async_Event {

	/**
	 * Per-request guard keyed on "{subscription}:{order}:{direction}".
	 *
	 * WooCommerce Subscriptions fires woocommerce_subscription_item_switched once per
	 * switched line item, so without this several items switched in the same direction on
	 * one subscription would schedule the async event (and run the workflow) once per item.
	 * Distinct directions are kept separate, since the trigger filters on switch direction.
	 *
	 * @var array
	 */
	private $scheduled_switches = [];

	/**
	 * Init the event.
	 */
	public function init() {
		add_action( 'woocommerce_subscription_item_switched', [ $this, 'schedule_event' ], 20, 4 );
	}

	/**
	 * Get the async event hook name.
	 *
	 * @since 6.4.0
	 *
	 * @return string
	 */
	public function get_hook_name(): string {
		return 'automatewoo/subscription/switched_async';
	}

	/**
	 * Schedule async event.
	 *
	 * @param WC_Order        $order
	 * @param WC_Subscription $subscription
	 * @param int             $new_item_id  New subscription item ID.
	 * @param int             $old_item_id  Old subscription item ID.
	 */
	public function schedule_event( $order, $subscription, $new_item_id, $old_item_id ) {
		$subscription_id  = $subscription->get_id();
		$order_id         = $order->get_id();
		$switch_direction = $this->get_switch_direction( $order, $subscription_id, $new_item_id );
		$dedup_key        = "{$subscription_id}:{$order_id}:{$switch_direction}";

		if ( isset( $this->scheduled_switches[ $dedup_key ] ) ) {
			return;
		}
		$this->scheduled_switches[ $dedup_key ] = true;

		$this->create_async_event( [ $subscription_id, $order_id, $switch_direction ] );
	}

	/**
	 * Get the switch direction for a specific subscription item from order meta.
	 *
	 * @param WC_Order $order
	 * @param int      $subscription_id
	 * @param int      $new_item_id
	 *
	 * @return string 'upgrade', 'downgrade', 'crossgrade', or empty string if not found.
	 */
	private function get_switch_direction( WC_Order $order, int $subscription_id, int $new_item_id ): string {
		$switch_data = $order->get_meta( '_subscription_switch_data' );

		if ( ! is_array( $switch_data ) || ! isset( $switch_data[ $subscription_id ]['switches'] ) ) {
			return '';
		}

		foreach ( $switch_data[ $subscription_id ]['switches'] as $item_data ) {
			if ( isset( $item_data['add_line_item'] ) && (int) $item_data['add_line_item'] === (int) $new_item_id ) {
				return $item_data['switch_direction'] ?? '';
			}
		}

		return '';
	}
}
