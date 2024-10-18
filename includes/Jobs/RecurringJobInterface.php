<?php

namespace AutomateWoo\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Interface RecurrentJobInterface.
 *
 * Jobs that implement this interface will run recurrently based on an interval.
 *
 * @since 5.8.1
 */
interface RecurringJobInterface extends JobInterface {

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int The interval for the action
	 */
	public function get_interval();

	/**
	 * Return the hook used in the Action Scheduler recurring action.
	 *
	 * @return string The hook name
	 */
	public function get_schedule_hook();

	/**
	 * Init the job recurrence.
	 */
	public function schedule_recurring();

	/**
	 * Cancels the job recurrence.
	 */
	public function cancel_recurring();

	/**
	 * Get the next scheduled job
	 */
	public function get_schedule();

	/**
	 * Determine if the job is enabled
	 *
	 * @since 6.0.28
	 */
	public function is_enabled();
}
