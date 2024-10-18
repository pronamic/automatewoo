<?php

namespace AutomateWoo\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * A Recurring Job without Batches.
 *
 * The way it works:
 * - Since it's a recurrent, JobService Schedules the job on WordPress "init" on the specified interval.
 * - We define the get_process_item_hook() as a hook in get_scheduled_hook() to be triggered when the scheduled action runs.
 * - When get_process_item_hook() is triggered, handle_process_item() is called.
 * - In handle_process_item() we call directly the validate_item() and process_item() methods.
 *
 * @since 6.0.0
 */
abstract class AbstractRecurringOneTimeActionSchedulerJob extends AbstractOneTimeActionSchedulerJob implements RecurringJobInterface {

	/**
	 * Get the start hook name for this Job.
	 * In this kind of jobs we want to use just the `automatewoo/jobs/{job_name}`.
	 */
	public function get_process_item_hook() {
		return 'automatewoo/jobs/' . $this->get_name();
	}

	/**
	 * We trigger process item hook on each scheduled action.
	 */
	public function get_schedule_hook() {
		return $this->get_process_item_hook();
	}

	/**
	 * In this kind of jobs we don't need validation.
	 *
	 * @param array $item
	 *
	 * @return bool Always true in this kind of Jobs.
	 */
	protected function validate_item( array $item ) {
		return true;
	}
}
