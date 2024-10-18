<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Traits\ArrayValidator;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractBatchedActionSchedulerJob class.
 *
 * Enables a job to be processed in recurring scheduled batches with queued events.
 *
 * Notes:
 * - Uses ActionScheduler's very scalable async actions feature which will run async batches in loop back requests until all batches are done
 * - Items may be processed concurrently by AS, but batches will be created one after the other, not concurrently
 * - The job will not start if it is already running
 *
 * @since 5.1.0
 */
abstract class AbstractBatchedActionSchedulerJob extends AbstractActionSchedulerJob implements BatchedActionSchedulerJobInterface {

	use ArrayValidator;

	/**
	 * Whether multiple instances of the job should be allowed to run concurrently.
	 *
	 * If the job is scheduled to run frequently, e.g. in less than 10 minute intervals, it's a good idea
	 * to disallow concurrency to prevent race conditions.
	 *
	 * @var bool
	 */
	protected $allow_concurrent = false;

	/**
	 * Get a new batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args         The args for this instance of the job. Args are already validated.
	 *
	 * @return string[]|int[]|array[]
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	abstract protected function get_batch( int $batch_number, array $args );

	/**
	 * Process a single item.
	 *
	 * @param string|int|array $item A single item from the get_batch() method. Expects a validated item.
	 * @param array            $args The args for this instance of the job. Args are already validated.
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	abstract protected function process_item( $item, array $args );

	/**
	 * Validate an item to be processed by the job.
	 *
	 * @param mixed $item
	 *
	 * @throws InvalidArgument If the item is not valid.
	 */
	abstract protected function validate_item( $item );

	/**
	 * Can the job start.
	 *
	 * @return bool Returns true if the job can start.
	 *
	 * @throws Exception An exception may be thrown in child method.
	 */
	protected function can_start(): bool {
		if ( false === $this->allow_concurrent && $this->is_running() ) {
			return false;
		}

		return true;
	}

	/**
	 * Init the batch schedule for the job.
	 *
	 * The job name is used to generate the schedule event name.
	 */
	public function init() {
		add_action( $this->get_create_batch_hook(), [ $this, 'handle_create_batch_action' ], 10, 2 );
		add_action( $this->get_process_item_hook(), [ $this, 'handle_process_item_action' ], 10, 2 );
	}

	/**
	 * Get the hook name for the "create batch" action.
	 *
	 * @return string
	 */
	protected function get_create_batch_hook() {
		return $this->get_hook_base_name() . 'create_batch';
	}

	/**
	 * Get the name of an action to attach the job's start method to.
	 * By default, it's 'automatewoo/jobs/{job_name}/start' but it can be overridden in Job implementation.
	 *
	 * @return string
	 */
	public function get_start_hook() {
		return $this->get_hook_base_name() . 'start';
	}

	/**
	 * Enqueue the "create_batch" action provided it doesn't already exist.
	 *
	 * To make minimize the resource use of starting the job the batch creation is handled async.
	 *
	 * @param array $args Optionally set args to be available during this instance of the job.
	 *
	 * @throws InvalidArgument If args are invalid.
	 * @throws Exception An exception may be thrown from child class.
	 */
	public function start( array $args = [] ) {
		$this->validate_args( $args );

		if ( $this->can_start() ) {
			$this->schedule_create_batch_action( 1, $args );
		}
	}

	/**
	 * Handles batch creation action hook.
	 *
	 * @hooked automatewoo/jobs/{$job_name}/create_batch
	 *
	 * Schedules an action to run immediately for each item in the batch.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args         The args for this instance of the job.
	 *
	 * @throws Exception If an error occurs.
	 * @throws JobException If the job failure rate is too high.
	 * @throws InvalidArgument If args or an item is invalid.
	 */
	public function handle_create_batch_action( int $batch_number, array $args ) {
		$this->monitor->validate_failure_rate( $this );
		$this->validate_args( $args );

		$items = $this->get_batch( $batch_number, $args );

		foreach ( $items as $item ) {
			$this->validate_item( $item );
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $item, $args ] );
		}

		if ( empty( $items ) ) {
			// If no more items the job is complete
			$this->handle_complete( $batch_number, $args );
		} else {
			// If items, schedule another "create_batch" action to handle remaining items
			$this->schedule_create_batch_action( $batch_number + 1, $args );
		}
	}

	/**
	 * Get job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size() {
		return 15;
	}

	/**
	 * Get the query offset based on a given batch number and the specified batch size.
	 *
	 * @param int $batch_number
	 *
	 * @return int
	 */
	protected function get_query_offset( int $batch_number ): int {
		return $this->get_batch_size() * ( $batch_number - 1 );
	}

	/**
	 * Handles processing single item action hook.
	 *
	 * @hooked automatewoo/jobs/{$job_name}/process_item
	 *
	 * @param mixed $item A single job item from the current batch.
	 * @param array $args The args for this instance of the job.
	 *
	 * @throws Exception If an error occurs.
	 * @throws InvalidArgument If args or an item is invalid.
	 */
	public function handle_process_item_action( $item, array $args ) {
		$this->validate_args( $args );
		$this->validate_item( $item );
		$this->process_item( $item, $args );
	}

	/**
	 * Schedule a new "create batch" action to run immediately.
	 *
	 * @param int   $batch_number The batch number for the new batch.
	 * @param array $args         The args for this instance of the job.
	 */
	protected function schedule_create_batch_action( int $batch_number, array $args ) {
		$this->action_scheduler->schedule_immediate( $this->get_create_batch_hook(), [ $batch_number, $args ] );
	}

	/**
	 * Check if this job is running.
	 *
	 * The job is considered to be running if a "create_batch" action is currently pending or in-progress.
	 *
	 * @return bool
	 */
	protected function is_running(): bool {
		return false !== $this->action_scheduler->next_scheduled_action( $this->get_create_batch_hook() );
	}

	/**
	 * Validate the job args.
	 *
	 * @param array $args The args for this instance of the job.
	 */
	protected function validate_args( array $args ) {
		// Optionally over-ride this method in child class.
	}

	/**
	 * Called when the job is completed.
	 *
	 * @param int   $final_batch_number The final batch number when the job was completed.
	 *                                  If equal to 1 then no items were processed by the job.
	 * @param array $args               The args for this instance of the job.
	 */
	protected function handle_complete( int $final_batch_number, array $args ) {
		// Optionally over-ride this method in child class.
	}
}
