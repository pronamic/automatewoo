<?php

namespace AutomateWoo\Orders\Observers;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;
use AutomateWoo\Customer;
use AutomateWoo\Customer_Factory;
use AutomateWoo\Orders\Observers\Traits\HandleOrderDeleted;
use AutomateWoo\Orders\Observers\Traits\HandleOrderStatusChanged;
use AutomateWoo\Orders\StatusTransition;
use WC_Order;

/**
 * Class CustomerLastPurchasedDateUpdater
 *
 * Updates the 'last_purchased' customer field based on order activity.
 *
 * @since 5.2.0
 */
class CustomerLastPurchasedDateUpdater {

	use HandleOrderStatusChanged;
	use HandleOrderDeleted;

	/**
	 * @var ActionSchedulerInterface
	 */
	protected $action_scheduler;

	/**
	 * CustomerLastPurchasedDateUpdater constructor.
	 *
	 * @param ActionSchedulerInterface $action_scheduler
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler ) {
		$this->action_scheduler = $action_scheduler;
	}

	/**
	 * Register hooks.
	 */
	public function register() {
		$this->add_handle_order_status_changed_hooks();
		$this->add_handle_order_deleted_hooks();

		add_action( 'automatewoo/async_update_customer_last_purchase_date', [ $this, 'process_async_update' ] );
	}

	/**
	 * Handle an order status change.
	 *
	 * @param WC_Order         $order
	 * @param StatusTransition $transition
	 */
	protected function handle_order_status_changed( WC_Order $order, StatusTransition $transition ) {
		$customer = Customer_Factory::get_by_order( $order );
		if ( ! $customer ) {
			return;
		}

		if ( $transition->is_becoming_paid() ) {
			$this->process_paid_order_update( $customer, $order );
		} elseif ( $transition->is_becoming_unpaid() ) {
			$this->process_update( $customer );
		}
	}

	/**
	 * Handle before order is deleted or trashed.
	 *
	 * @param WC_Order $order
	 */
	protected function handle_order_deleted( WC_Order $order ) {
		$customer = Customer_Factory::get_by_order( $order );
		if ( $customer ) {
			// Process the update after the order deletion has finished.
			$this->action_scheduler->schedule_immediate(
				'automatewoo/async_update_customer_last_purchase_date',
				[ $customer->get_id() ]
			);
		}
	}

	/**
	 * @param int $customer_id
	 */
	public function process_async_update( int $customer_id ) {
		$customer = Customer_Factory::get( $customer_id );
		if ( $customer ) {
			$this->process_update( $customer );
		}
	}

	/**
	 * Update last_purchased date for a newly paid order.
	 *
	 * @since 6.5.0
	 *
	 * @param Customer $customer
	 * @param WC_Order $order
	 */
	protected function process_paid_order_update( Customer $customer, WC_Order $order ) {
		$order_created = $order->get_date_created();
		if ( ! $order_created ) {
			$this->process_update( $customer );
			return;
		}

		$last_purchased = $customer->get_date_last_purchased();
		if ( $last_purchased && $last_purchased->getTimestamp() >= $order_created->getTimestamp() ) {
			return;
		}

		// Without a known last purchase date, older status changes still need a full recalculation.
		if ( ! $last_purchased && $order_created->getTimestamp() < time() - MINUTE_IN_SECONDS ) {
			$this->process_update( $customer );
			return;
		}

		$customer->set_date_last_purchased( $order_created );
		$customer->save();
	}

	/**
	 * Recalculate last_purchased date for customer.
	 *
	 * @param Customer $customer
	 */
	protected function process_update( Customer $customer ) {
		$customer->recache_date_last_purchased();
	}
}
