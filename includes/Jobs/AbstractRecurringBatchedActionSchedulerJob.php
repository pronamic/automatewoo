<?php

namespace AutomateWoo\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * A Recurring Job with Batches.
 *
 * The way it works:
 * - Since it's a recurrent, JobService Schedules the job on WordPress "init" on the specified interval.
 * - We define the get_start_hook() as a hook in get_scheduled_hook() to be triggered when the scheduled action runs.
 * - When get_start_hook() is triggered, start() method is called
 * - In start() method then creates the batches.
 *
 * @since 6.0.0
 */
abstract class AbstractRecurringBatchedActionSchedulerJob extends AbstractBatchedActionSchedulerJob implements RecurringJobInterface, StartOnHookInterface {

	/**
	 * Since it's a batched action, when this Action schedules it will trigger the start hook.
	 */
	public function get_schedule_hook() {
		return $this->get_start_hook();
	}
}
