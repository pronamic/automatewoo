<?php

namespace AutomateWoo\Async_Events;

use AutomateWoo\Clean;
use AutomateWoo\Subscription_Workflow_Helper;
use WC_Order;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Created
 *
 * @since 4.8.0
 */
class Order_Created extends Abstract_Async_Event {

	use UniqueEventsForRequestHelper;

	const MAYBE_ORDER_CREATED_HOOK = 'automatewoo/async/maybe_order_created';
	const ORDER_CREATED_META_KEY   = '_automatewoo_order_created';

	/**
	 * Init the event.
	 */
	public function init() {
		add_action( 'woocommerce_new_order', [ $this, 'enqueue_maybe_order_created_async_event' ], 100, 2 );
		add_action( 'woocommerce_order_status_changed', [ $this, 'handle_order_status_changed' ], 50, 4 );
		add_action( self::MAYBE_ORDER_CREATED_HOOK, [ $this, 'maybe_do_order_created_action' ] );
	}

	/**
	 * Handle order status change.
	 *
	 * @param int      $order_id
	 * @param string   $old_status
	 * @param string   $new_status
	 * @param WC_Order $order
	 *
	 * @since 5.5.23
	 */
	public function handle_order_status_changed( int $order_id, string $old_status, string $new_status, WC_Order $order ) {
		$draft_statuses = aw_get_draft_order_statuses();

		// ensure that the old status IS a draft status and the new status IS NOT a draft status
		if ( in_array( $old_status, $draft_statuses, true ) && ! in_array( $new_status, $draft_statuses, true ) ) {
			$this->enqueue_maybe_order_created_async_event( $order_id );
		}
	}

	/**
	 * Handle post status transition.
	 *
	 * @param string  $new_status
	 * @param string  $old_status
	 * @param WP_Post $post
	 *
	 * @deprecated use \AutomateWoo\Async_Events\Order_Created::handle_order_status_changed()
	 */
	public function handle_transition_post_status( string $new_status, string $old_status, WP_Post $post ) {
		if ( $post->post_type !== 'shop_order' ) {
			return;
		}

		wc_deprecated_function( __METHOD__, '5.6.0', '\AutomateWoo\Async_Events\Order_Created::handle_order_status_changed' );
		$this->handle_order_status_changed( $post->ID, $old_status, $new_status, wc_get_order( $post->ID ) );
	}

	/**
	 * An order was created.
	 *
	 * @param int|string $order_id
	 * @param WC_Order   $order
	 */
	public function enqueue_maybe_order_created_async_event( $order_id, $order = null ) {
		$order_id = Clean::id( $order_id );
		$order    = $order ? $order : wc_get_order( $order_id );
		if ( ! $order || Subscription_Workflow_Helper::is_subscription( $order_id ) ) {
			return;
		}

		// Creating a draft order triggers woocommerce_new_order, but we don't want it to trigger this workflow.
		if ( in_array( $order->get_status(), aw_get_draft_order_statuses(), true ) ) {
			return;
		}
		// Due to the variety of order created hooks, protect against adding multiple events for the same order_id
		if ( $this->check_item_is_unique_for_event( $order_id ) ) {
			return;
		}

		$this->record_event_added_for_item( $order_id );

		// Enqueue the async action on shutdown to ensure the event doesn't happen before the order is fully created
		$this->action_scheduler->enqueue_async_action_on_shutdown( self::MAYBE_ORDER_CREATED_HOOK, [ $order_id ] );
	}

	/**
	 * Handles async order created event.
	 *
	 * Prevents duplicate events from running with a meta check.
	 *
	 * @param int $order_id
	 */
	public function maybe_do_order_created_action( int $order_id ) {
		$order = wc_get_order( Clean::id( $order_id ) );
		if ( ! $order || $order->get_meta( self::ORDER_CREATED_META_KEY ) || Subscription_Workflow_Helper::is_subscription( $order_id ) ) {
			return;
		}

		$order->update_meta_data( self::ORDER_CREATED_META_KEY, true );
		$order->save();

		// do real async order created action
		do_action( $this->get_hook_name(), $order_id );
	}
}
