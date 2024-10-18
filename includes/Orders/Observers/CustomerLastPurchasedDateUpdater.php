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

		if ( $transition->is_becoming_paid() || $transition->is_becoming_unpaid() ) {
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
	 * Recalculate last_purchased date for customer.
	 *
	 * @param Customer $customer
	 */
	protected function process_update( Customer $customer ) {
		$last_paid_order = $customer->get_nth_last_paid_order( 1 );

		if ( $last_paid_order ) {
			$date = $last_paid_order->get_date_created();
		} else {
			$date = null;
		}

		$customer->set_date_last_purchased( $date );
		$customer->save();
	}
}
