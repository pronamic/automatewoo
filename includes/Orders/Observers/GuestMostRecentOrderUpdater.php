<?php

namespace AutomateWoo\Orders\Observers;

use AutomateWoo\Customer_Factory;
use AutomateWoo\Orders\Observers\Traits\HandleOrderDeleted;
use AutomateWoo\Orders\Observers\Traits\HandleOrderStatusChanged;
use AutomateWoo\Orders\StatusTransition;
use WC_Order;

/**
 * Class GuestMostRecentOrderUpdater
 *
 * Updates the guests most recent order prop based on order activity.
 *
 * @since 5.2.0
 */
class GuestMostRecentOrderUpdater {

	use HandleOrderStatusChanged;
	use HandleOrderDeleted;

	/**
	 * Register hooks.
	 */
	public function register() {
		$this->add_handle_order_status_changed_hooks();
		$this->add_handle_order_deleted_hooks();
	}

	/**
	 * Handle an order status change.
	 *
	 * @param WC_Order         $order
	 * @param StatusTransition $transition
	 */
	protected function handle_order_status_changed( WC_Order $order, StatusTransition $transition ) {
		if ( $transition->is_becoming_counted() || $transition->is_becoming_uncounted() ) {
			$this->process_update( $order );
		}
	}

	/**
	 * Handle before order is deleted or trashed.
	 *
	 * @param WC_Order $order
	 */
	protected function handle_order_deleted( WC_Order $order ) {
		$this->process_update( $order );
	}

	/**
	 * Recalculate last_purchased date for customer.
	 *
	 * @param WC_Order $order
	 */
	protected function process_update( WC_Order $order ) {
		if ( $order->get_user_id() ) {
			// Order made by registered user
			return;
		}

		// get customer, also creates the guest if needed
		$customer = Customer_Factory::get_by_order( $order );
		if ( ! $customer ) {
			return;
		}

		$guest = $customer->get_guest();
		if ( ! $guest ) {
			return;
		}

		$guest->recache_most_recent_order_id();
	}
}
