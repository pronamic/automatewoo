<?php

namespace AutomateWoo\SystemChecks;

use AutomateWoo\Jobs\JobInterface;
use AutomateWoo\Jobs\RecurringJobInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action Scheduler Jobs system check class.
 *
 * @package AutomateWoo\SystemChecks
 */
class ActionSchedulerJobsRunning extends AbstractSystemCheck {

	/**
	 * The available Jobs
	 *
	 * @var JobInterface[]
	 */
	public $jobs;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->title         = __( 'AutomateWoo Action Scheduler Jobs', 'automatewoo' );
		$this->description   = __( 'Checks the dates of Scheduled Jobs to see if they are correctly setup.', 'automatewoo' );
		$this->high_priority = true;
		$this->jobs          = AW()->job_service()->get_jobs();
	}


	/**
	 * Perform the check
	 */
	public function run() {
		$failed = false;

		foreach ( $this->jobs as $job ) {

			if ( ! $job instanceof RecurringJobInterface || ! $job->is_enabled() ) {
				continue;
			}

			$is_scheduled = as_has_scheduled_action( $job->get_schedule_hook() );

			if ( ! $is_scheduled ) {
				$failed = true;
			}
		}

		if ( $failed ) {
			return $this->error( __( 'Some Scheduled Jobs are not correctly setup. Action Scheduled Jobs are heavily relied upon by AutomateWoo. It might be that WP Cron is not running. Please contact your hosting provider to resolve the issue.', 'automatewoo' ) );
		}

		return $this->success();
	}
}
