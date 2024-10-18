<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Customer_Factory;
use AutomateWoo\Jobs\Traits\ValidateItemAsIntegerId;
use Exception;

/**
 * Goes through every guest order and creates a customer for it.
 *
 * @since 5.2.0
 */
class SetupGuestCustomers extends AbstractRecurringBatchedActionSchedulerJob {

	use ValidateItemAsIntegerId;

	/**
	 * Setup guest customer complete option name
	 *
	 * @var string
	 */
	protected $complete_option = '_automatewoo_setup_guest_customers_complete';

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'setup_guest_customers';
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @since 6.0.0
	 * @return int The interval for the action in seconds
	 */
	public function get_interval() {
		return JobService::FOUR_HOURS_INTERVAL;
	}

	/**
	 * Process a single item.
	 *
	 * @param int   $order_id
	 * @param array $args     The args for this instance of the job. Args are already validated.
	 *
	 * @throws JobException If item can't be found.
	 */
	protected function process_item( $order_id, array $args ) {

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			throw JobException::item_not_found();
		}

		$customer = Customer_Factory::get_by_order( $order );
		if ( ! $customer ) {
			throw JobException::item_not_found();
		}

		if ( ! $customer->get_date_last_purchased() ) {

			// set the last purchase date
			$orders = wc_get_orders(
				[
					'type'     => 'shop_order',
					'status'   => wc_get_is_paid_statuses(),
					'limit'    => 1,
					'customer' => $customer->get_email(),
					'orderby'  => 'date',
					'order'    => 'DESC',
				]
			);

			if ( $orders ) {
				$customer->set_date_last_purchased( $orders[0]->get_date_created() );
				$customer->save();
			}
		}
	}

	/**
	 * Get a new batch of items.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args         The args for this instance of the job. Args are already validated.
	 *
	 * @return int[]
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function get_batch( int $batch_number, array $args ) {

		if ( get_option( $this->complete_option ) ) {
			return [];
		}

		// guest orders
		return wc_get_orders(
			[
				'type'        => 'shop_order',
				'limit'       => $this->get_batch_size(),
				'offset'      => $this->get_query_offset( $batch_number ),
				'status'      => wc_get_is_paid_statuses(),
				'customer_id' => 0,
				'return'      => 'ids',

				// order by ascending ID since new orders could be created while the job is running which would throw the offset off
				'orderby'     => 'ID',
				'order'       => 'ASC',

				// exlude anonymized orders ( see https://github.com/woocommerce/automatewoo/issues/1643 )
				'anonymized'  => false,
			]
		);
	}

	/**
	 * Can the job start.
	 *
	 * @return bool Returns true if the job can start.
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function can_start(): bool {
		if ( get_option( $this->complete_option ) ) {
			return false;
		}

		return parent::can_start();
	}

	/**
	 * Called when the job is completed.
	 *
	 * @param int   $final_batch_number The final batch number when the job was completed.
	 *                                  If equal to 1 then no items were processed by the job.
	 * @param array $args               The args for this instance of the job.
	 */
	protected function handle_complete( int $final_batch_number, array $args ) {
		update_option( $this->complete_option, true, false );
	}
}
