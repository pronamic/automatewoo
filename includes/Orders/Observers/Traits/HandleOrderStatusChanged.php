<?php

namespace AutomateWoo\Orders\Observers\Traits;

use AutomateWoo\Orders\StatusTransition;
use WC_Order;

/**
 * Trait HandleOrderStatusChanged
 *
 * @since 5.2.0
 */
trait HandleOrderStatusChanged {

	/**
	 * Handle an order status change.
	 *
	 * @param WC_Order         $order
	 * @param StatusTransition $transition
	 */
	abstract protected function handle_order_status_changed( WC_Order $order, StatusTransition $transition );

	/**
	 * Add hooks.
	 */
	protected function add_handle_order_status_changed_hooks() {
		add_action( 'woocommerce_order_status_changed', [ $this, 'handle_initial_order_status_changed' ], 10, 3 );
	}

	/**
	 * Handle the initial order status change action.
	 *
	 * @param int    $order_id
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function handle_initial_order_status_changed( int $order_id, string $old_status, string $new_status ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$this->handle_order_status_changed( $order, new StatusTransition( $old_status, $new_status ) );
	}
}
