<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Initializes a Job running every day at Midnight
 * which just triggers the hook `automatewoo_midnight`.
 *
 * This Job is essential for running Custom Date Time Workflows recurrently.
 * The correctness of the Midnight Job time is based on the site timezone, so the changes on the Timezone will
 * update also this Job. There is another Job CheckMidnightJob doing this.
 *
 * @since 6.0.0
 */
class Midnight extends AbstractRecurringOneTimeActionSchedulerJob {


	/**
	 * Update the last run in database.
	 *
	 * @param array $item  We don't process any item for this Job
	 */
	protected function process_item( array $item ) {
		$this->update_midnight_job_last_run();
	}

	/**
	 * Gets the name of this Job
	 *
	 * @return string The name of this Job
	 */
	public function get_name() {
		return 'automatewoo_midnight';
	}

	/**
	 * Get the start hook name for this Job.
	 * In midnight job we want to use just the `{job_name}` for compatibility.
	 */
	public function get_process_item_hook() {
		return $this->get_name();
	}

	/**
	 * Gets the interval for this Job in seconds.
	 *
	 * @return int The interval for this Job in seconds.
	 */
	public function get_interval() {
		return JobService::ONE_DAY_INTERVAL;
	}

	/**
	 * We are overriding schedule_recurring() here since we need a specific midnight time to run the job.
	 *
	 * So this function calculates the next midnight in the site's timezone and schedules the midnight
	 * job based on that date.
	 *
	 * Notice we get "now" instead of "tomorrow", to avoid issues with custom time of day triggers.
	 * These triggers could skip 1 day if we don't run the midnight cron immediately when adding it.
	 */
	public function schedule_recurring() {
		$date = new DateTime( 'now' );
		$date->convert_to_site_time();
		$date->set_time_to_day_start();
		$date->convert_to_utc_time();

		$this->schedule_midnight_job( $date );
	}

	/**
	 * Schedules a Midnight Job with a specific start date.
	 *
	 * @param DateTime $date The start date for the Midnight Job
	 */
	public function schedule_midnight_job( DateTime $date ) {
		if ( ! $this->get_schedule() ) {
			$this->action_scheduler->schedule_recurring_action(
				$date->getTimestamp(),
				$this->get_interval(),
				$this->get_schedule_hook()
			);
		}
	}


	/**
	 * Update the last run date of the midnight job to now.
	 *
	 * Store last run in site time as Y-m-d.
	 *
	 * This is stored as site time because the goal of the midnight cron event is to run once per day
	 * in the site's timezone. Storing in site time means we can handle DST timezone changes better.
	 */
	public function update_midnight_job_last_run() {
		$now = new DateTime();
		$now->convert_to_site_time();
		update_option( 'automatewoo_midnight_cron_last_run', $now->format( 'Y-m-d' ), false );
	}

	/**
	 * Get the last run date of the midnight job in site time.
	 *
	 * @return DateTime|false The last run normalized date. False if the midnight job didn't run .
	 */
	public function get_midnight_job_last_run() {
		$last_run = get_option( 'automatewoo_midnight_cron_last_run' );
		return $last_run ? aw_normalize_date( $last_run ) : false;
	}


	/**
	 * Did the midnight job run today (in local time)?
	 * Also returns true if midnight job has run for tomorrow. E.g. in the case of DST changes.
	 *
	 * @return bool
	 */
	public function should_midnight_job_run_today() {
		$last_run = $this->get_midnight_job_last_run();

		if ( ! $last_run ) {
			return true;
		}

		$last_run->set_time_to_day_end();

		$now = new DateTime();
		$now->convert_to_site_time();

		// Return false if cron has run today or even for tomorrow
		return $now > $last_run;
	}

	/**
	 * Check if the Midnight Job is correctly setup.
	 * The Midnight Job is correctly setup if the action scheduled time (in site's timezone) is set at midnight.
	 *
	 * @return bool True if the Midnight Job is correctly setup
	 */
	public function is_midnight_job_correct() {
		$action_timestamp = $this->get_schedule();
		if ( ! $action_timestamp ) {
			return false;
		}
		$date = new DateTime();
		$date->setTimestamp( $action_timestamp );

		$date->convert_to_site_time();
		return $date->format( 'Hi' ) === '0000';
	}
}
