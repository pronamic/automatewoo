<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Customer_Factory;
use AutomateWoo\Cron;
use AutomateWoo\Clean;
use AutomateWoo\Jobs\Traits\ValidateItemAsIntegerId;
use Exception;

/**
 * Creates customer records for all registered users.
 *
 * @since 5.2.0
 */
class SetupRegisteredCustomers extends AbstractBatchedActionSchedulerJob implements StartOnHookInterface {

	use ValidateItemAsIntegerId;

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'setup_registered_customers';
	}

	/**
	 * Get the name of an action to attach the job's start method to.
	 *
	 * @return string
	 */
	public function get_start_hook() {
		return 'automatewoo_updated_async';
	}

	/**
	 * Process a single item.
	 *
	 * @param int   $user_id
	 * @param array $args    The args for this instance of the job. Args are already validated.
	 *
	 * @throws JobException If item can't be found.
	 */
	protected function process_item( $user_id, array $args ) {

		// get/create a customer record
		$customer = Customer_Factory::get_by_user_id( $user_id );

		if ( ! $customer ) {
			// error getting/creating the customer
			throw JobException::item_not_found();
		}

		// set the last purchase date
		$orders = wc_get_orders(
			[
				'type'     => 'shop_order',
				'status'   => wc_get_is_paid_statuses(),
				'limit'    => 1,
				'customer' => $user_id,
				'orderby'  => 'date',
				'order'    => 'DESC',
			]
		);

		if ( $orders ) {
			$customer->set_date_last_purchased( $orders[0]->get_date_created() );
			$customer->save();
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

		global $wpdb;

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} WHERE ID NOT IN ( SELECT user_id FROM {$wpdb->prefix}automatewoo_customers ) ORDER BY ID ASC LIMIT %d",
				$this->get_batch_size()
			)
		);

		return Clean::ids( $results );
	}

	/**
	 * Called when the job is completed.
	 *
	 * @param int   $final_batch_number The final batch number when the job was completed.
	 *                                  If equal to 1 then no items were processed by the job.
	 * @param array $args               The args for this instance of the job.
	 *
	 * @throws JobException If the job is not found.
	 */
	protected function handle_complete( int $final_batch_number, array $args ) {
		AW()->job_service()->get_job( 'setup_guest_customers' )->start();
	}
}
