<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;
use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Jobs\Traits\ValidateItemAsIntegerId;
use AutomateWoo\LogCleanupService;
use AutomateWoo\OptionsStore;

defined( 'ABSPATH' ) || exit;

/**
 * Recurring job that deletes workflow logs older than the configured retention period.
 *
 * Uses `LogCleanupService` for the actual deletion and cache invalidation so
 * the same logic is shared with the `wp automatewoo delete-old-logs` CLI command.
 *
 * @since 6.3.2
 */
class DeleteOldLogs extends AbstractRecurringBatchedActionSchedulerJob {

	use ValidateItemAsIntegerId;

	/**
	 * @var OptionsStore
	 */
	protected $options_store;

	/**
	 * @var LogCleanupService
	 */
	protected $log_cleanup_service;

	/**
	 * DeleteOldLogs constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler    Action scheduler instance.
	 * @param ActionSchedulerJobMonitor $monitor             Job monitor instance.
	 * @param OptionsStore              $options_store       Options store instance.
	 * @param LogCleanupService         $log_cleanup_service Log cleanup service instance.
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		OptionsStore $options_store,
		LogCleanupService $log_cleanup_service
	) {
		$this->options_store       = $options_store;
		$this->log_cleanup_service = $log_cleanup_service;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'delete_old_logs';
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int
	 */
	public function get_interval() {
		return JobService::FOUR_HOURS_INTERVAL;
	}

	/**
	 * Can the job start.
	 *
	 * Only starts when log retention is configured (> 0 months).
	 *
	 * @return bool
	 *
	 * @throws InvalidArgument If option value is invalid.
	 */
	protected function can_start(): bool {
		if ( $this->options_store->get_log_retention_months() <= 0 ) {
			return false;
		}

		return parent::can_start();
	}

	/**
	 * Get a new batch of log IDs to delete.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args         The args for this instance of the job.
	 *
	 * @return int[]
	 */
	protected function get_batch( int $batch_number, array $args ) {
		$retention_months = $this->options_store->get_log_retention_months();
		$cutoff_date      = $this->log_cleanup_service->get_retention_cutoff_date( $retention_months );

		if ( ! $cutoff_date ) {
			return [];
		}

		return $this->log_cleanup_service->get_log_ids_before_date( $cutoff_date, $this->get_batch_size() );
	}

	/**
	 * Process a single item (delete a single log, its meta, and related conversion meta).
	 *
	 * @param int   $log_id The log ID to delete.
	 * @param array $args   The args for this instance of the job.
	 */
	protected function process_item( $log_id, array $args ) {
		$this->log_cleanup_service->delete_log_with_cleanup( (int) $log_id );
	}

	/**
	 * Called when the job is completed.
	 *
	 * Invalidates log-related caches after all batches have been processed.
	 *
	 * @param int   $final_batch_number The final batch number when the job was completed.
	 * @param array $args               The args for this instance of the job.
	 */
	protected function handle_complete( int $final_batch_number, array $args ) {
		// Only invalidate caches if items were actually processed.
		if ( $final_batch_number > 1 ) {
			$this->log_cleanup_service->invalidate_log_caches();
		}
	}
}
