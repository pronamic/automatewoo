<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;
use AutomateWoo\DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Checks if the Midnight Job is correct and fix it if it's not
 *
 * @since 6.0.0
 */
class CheckMidnightJob extends AbstractRecurringOneTimeActionSchedulerJob {


	/**
	 * @var Midnight
	 */
	private $midnight_job;

	/**
	 * Constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param Midnight                  $midnight_job
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, Midnight $midnight_job ) {
		$this->midnight_job = $midnight_job;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'check_midnight_job';
	}

	/**
	 * Check the midnight job is correctly scheduled.
	 *
	 * If schedule is not correct this method fixes the schedule.
	 *
	 * @param array $item Not used for this job.
	 */
	public function process_item( array $item ) {
		if ( $this->midnight_job->is_midnight_job_correct() ) {
			return;
		}

		$this->midnight_job->cancel_recurring();

		// Repair the cron job schedule
		$date = new DateTime();
		$date->convert_to_site_time();
		$date->set_time_to_day_start();

		// If midnight job should not run for today, schedule it for tomorrow
		// Otherwise, run it now because it's better to run at a slightly wrong time rather than not run at all.
		if ( ! $this->midnight_job->should_midnight_job_run_today() ) {
			// Replace date with last run +1 day, i.e. the day after last run
			$date = $this->midnight_job->get_midnight_job_last_run();
			$date->modify( '+1 day' );
		}

		$date->convert_to_utc_time();

		$this->midnight_job->schedule_midnight_job( $date );
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int The interval for this job
	 */
	public function get_interval() {
		return JobService::THIRTY_MINUTE_INTERVAL;
	}
}
