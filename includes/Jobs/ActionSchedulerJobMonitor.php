<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class ActionSchedulerJobMonitor
 *
 * @since 5.1.0
 */
class ActionSchedulerJobMonitor {

	/**
	 * @var ActionSchedulerInterface
	 */
	protected $action_scheduler;

	/**
	 * ActionSchedulerInterface constructor.
	 *
	 * @param ActionSchedulerInterface $action_scheduler
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler ) {
		$this->action_scheduler = $action_scheduler;
	}

	/**
	 * Check whether the failure rate is above a threshold within the last hour.
	 *
	 * To protect against failing jobs running forever the job's failure rate is checked before creating a new batch.
	 * By default, a job is stopped if it has 5 failures in the last hour.
	 *
	 * @param ActionSchedulerJobInterface $job
	 *
	 * @throws JobException If the job's error rate is above the threshold.
	 */
	public function validate_failure_rate( ActionSchedulerJobInterface $job ) {
		$failed_actions = $this->action_scheduler->search(
			[
				'hook'         => $job->get_process_item_hook(),
				'status'       => $this->action_scheduler::STATUS_FAILED,
				'per_page'     => $this->get_failure_rate_threshold(),
				'date'         => gmdate( 'U' ) - HOUR_IN_SECONDS,
				'date_compare' => '>',
			],
			'ids'
		);

		if ( count( $failed_actions ) === $this->get_failure_rate_threshold() ) {
			throw JobException::stopped_due_to_high_failure_rate( esc_html( $job->get_name() ) );
		}
	}

	/**
	 * Get the batched job failure rate threshold (per hour).
	 *
	 * @return int
	 */
	protected function get_failure_rate_threshold() {
		return absint( apply_filters( 'automatewoo/batched_job_monitor/failure_rate_threshold', 5 ) );
	}
}
