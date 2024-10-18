<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\Cron;
use AutomateWoo\DateTime;
use AutomateWoo\Jobs\Traits\ValidateItemAsIntegerId;
use AutomateWoo\Queue_Query;
use AutomateWoo\Queued_Event_Factory;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Job to run queued workflow when they are scheduled to run.
 *
 * @since 5.1.0
 */
class RunQueuedWorkflows extends AbstractRecurringBatchedActionSchedulerJob {

	use ValidateItemAsIntegerId;

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'run_queued_workflows';
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @since 6.0.0
	 * @return int The interval for the action in seconds
	 */
	public function get_interval(): int {
		return JobService::TWO_MINUTE_INTERVAL;
	}

	/**
	 * Can the job start.
	 *
	 * Because this job runs every 2 minutes this method is over-ridden here to prevent a create batch action from
	 * being created every 2 minutes.
	 *
	 * @return bool Returns true if the job can start.
	 */
	protected function can_start(): bool {
		$query = ( new Queue_Query() )
			->set_ordering( 'date', 'ASC' )
			->where_date_due( new DateTime(), '<' )
			->where_failed( false );

		if ( ! $query->has_results() ) {
			return false;
		}

		return parent::can_start();
	}

	/**
	 * Get a new batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the a job cycle.
	 * @param array $args         The args for this instance of the job.
	 *
	 * @return int[]
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function get_batch( int $batch_number, array $args ) {
		$query = ( new Queue_Query() )
			->set_limit( $this->get_batch_size() )
			->set_ordering( 'date', 'ASC' )
			->where_date_due( new DateTime(), '<' )
			->where_failed( false );

		return $query->get_results_as_ids();
	}

	/**
	 * Process a single item.
	 *
	 * @param int   $item A single item from the get_batch() method. Expects a validated item.
	 * @param array $args The args for this instance of the job.
	 *
	 * @throws JobException When the item can't be processed.
	 */
	protected function process_item( $item, array $args ) {
		$queued_workflow = Queued_Event_Factory::get( $item );

		if ( ! $queued_workflow ) {
			throw JobException::item_not_found();
		}

		// Double-check if the event is not marked as failed
		if ( $queued_workflow->is_failed() ) {
			throw new JobException( esc_html__( 'Queued workflow is already marked as failed.', 'automatewoo' ) );
		}

		$queued_workflow->run();
	}
}
