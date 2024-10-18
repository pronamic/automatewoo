<?php

namespace AutomateWoo\Orders\Observers\Traits;

use WC_Order;

/**
 * Trait HandleOrderDeleted
 *
 * @since 5.2.0
 */
trait HandleOrderDeleted {

	/**
	 * Handle before order is deleted or trashed.
	 *
	 * @param WC_Order $order
	 */
	abstract protected function handle_order_deleted( WC_Order $order );

	/**
	 * Add hooks.
	 */
	protected function add_handle_order_deleted_hooks() {
		add_action( 'before_delete_post', [ $this, 'handle_post_trashed_or_deleted' ], 8 );
		add_action( 'wp_trash_post', [ $this, 'handle_post_trashed_or_deleted' ] );

		// These hooks are only triggered when HPOS is enabled.
		add_action( 'woocommerce_before_delete_order', [ $this, 'handle_order_trashed_or_deleted' ], 10, 2 );
		add_action( 'woocommerce_before_trash_order', [ $this, 'handle_order_trashed_or_deleted' ], 10, 2 );
	}

	/**
	 * Handle initial post trash and deletion.
	 * Triggered when the posts table is used for orders.
	 *
	 * @param int $post_id
	 */
	public function handle_post_trashed_or_deleted( int $post_id ) {
		if ( 'shop_order' !== get_post_type( $post_id ) ) {
			return;
		}

		$order = wc_get_order( $post_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$this->handle_order_deleted( $order );
	}

	/**
	 * Handle initial order trash and deletion.
	 * Triggered when HPOS is enabled.
	 *
	 * @param int      $order_id
	 * @param WC_Order $order
	 */
	public function handle_order_trashed_or_deleted( int $order_id, WC_Order $order ) {
		// Don't trigger the old hooks when not storing orders in the posts table.
		remove_action( 'before_delete_post', [ $this, 'handle_post_trashed_or_deleted' ], 8 );
		remove_action( 'wp_trash_post', [ $this, 'handle_post_trashed_or_deleted' ] );

		$this->handle_order_deleted( $order );
	}
}
