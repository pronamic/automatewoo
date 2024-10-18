<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;
use AutomateWoo\Cart;
use AutomateWoo\Cart_Factory;
use AutomateWoo\Cart_Query;
use AutomateWoo\DateTime;
use AutomateWoo\Jobs\Traits\ValidateItemAsIntegerId;
use AutomateWoo\OptionsStore;
use AutomateWoo\Options;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Finds active carts that appear to be abandoned and changes their status.
 *
 * @since 5.1.0
 */
class AbandonedCarts extends AbstractRecurringBatchedActionSchedulerJob {

	use ValidateItemAsIntegerId;

	/**
	 * @var OptionsStore $options_store
	 */
	protected $options_store;

	/**
	 * AbstractBatchedJob constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param OptionsStore              $options_store
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, OptionsStore $options_store ) {
		$this->options_store = $options_store;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'abandoned_carts';
	}

	/**
	 * Get the base abandoned carts query for this job.
	 *
	 * @return Cart_Query
	 * @throws Exception On date error.
	 */
	protected function get_base_abandoned_carts_query() {
		$timeout_date = new DateTime();
		$timeout_date->sub(
			new \DateInterval(
				sprintf( 'PT%dM', absint( $this->options_store->get_abandoned_cart_timeout() ) )
			)
		);

		return ( new Cart_Query() )
			->where_status( Cart::STATUS_ACTIVE )
			->where_date_modified( $timeout_date, '<' )
			->set_ordering( 'last_modified', 'DESC' );
	}

	/**
	 * Can the job start.
	 *
	 * Because this job runs every 2 minutes this method is over-ridden here to prevent a create batch action from
	 * being created every 2 minutes.
	 *
	 * @return bool Returns true if the job can start.
	 *
	 * @throws Exception On date parse error.
	 */
	protected function can_start(): bool {
		if (
			! $this->options_store->get_cart_tracking_enabled() ||
			! $this->get_base_abandoned_carts_query()->has_results() ) {
			return false;
		}

		return parent::can_start();
	}

	/**
	 * Get a new batch of items.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args         The args for this instance of the job. Args are already validated.
	 *
	 * @return int[]
	 * @throws Exception On date parse error.
	 */
	protected function get_batch( int $batch_number, array $args ) {
		return $this->get_base_abandoned_carts_query()
			->set_limit( $this->get_batch_size() )
			->get_results_as_ids();
	}

	/**
	 * Process a single item.
	 *
	 * @param int   $cart_id
	 * @param array $args The args for this instance of the job. Args are already validated.
	 *
	 * @throws JobException If item can't be found.
	 */
	protected function process_item( $cart_id, array $args ) {
		$cart = Cart_Factory::get( $cart_id );

		if ( ! $cart ) {
			throw JobException::item_not_found();
		}

		$cart->update_status( 'abandoned' );
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int The interval for this action
	 */
	public function get_interval(): int {
		return JobService::TWO_MINUTE_INTERVAL;
	}

	/**
	 * If cart tracking is not enabled then disable the job to prevent
	 * recurring actions from being scheduled.
	 *
	 * @since 6.0.28
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return Options::abandoned_cart_enabled();
	}
}
