<?php

namespace AutomateWoo\Triggers\Utilities;

use AutomateWoo\Order_Note;
use WC_Order;

/**
 * Trait HandleOrderNoteAdded.
 *
 * @since 5.2.0
 */
trait HandleOrderNoteAdded {

	/**
	 * Handle when an order note is added.
	 *
	 * @param Order_Note $order_note
	 * @param WC_Order   $order
	 */
	abstract protected function handle_order_note_added( Order_Note $order_note, WC_Order $order );

	/**
	 * Get order types to target in the order note trigger.
	 *
	 * @return array
	 */
	abstract protected function get_target_order_types(): array;

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_action( 'woocommerce_order_note_added', [ $this, 'handle_initial_order_note_added_action' ], 15, 2 );
	}

	/**
	 * Handle the initial `woocommerce_order_note_added` action.
	 *
	 * @param int|false $comment_id
	 * @param WC_Order  $order
	 */
	public function handle_initial_order_note_added_action( $comment_id, WC_Order $order ) {
		if ( ! $comment_id ) {
			return;
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}

		// Ensure the order matches one of the target order types
		if ( ! in_array( $order->get_type(), $this->get_target_order_types(), true ) ) {
			return;
		}

		$this->handle_order_note_added(
			new Order_Note( $comment->comment_ID, $comment->comment_content, $order->get_id() ),
			$order
		);
	}
}
