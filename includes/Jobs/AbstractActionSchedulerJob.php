<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractActionSchedulerJob class.
 *
 * Abstract class for jobs that use ActionScheduler.
 *
 * @since 5.2.0
 */
abstract class AbstractActionSchedulerJob implements ActionSchedulerJobInterface {

	/**
	 * @var ActionSchedulerInterface
	 */
	protected $action_scheduler;

	/**
	 * @var ActionSchedulerJobMonitor
	 */
	protected $monitor;

	/**
	 * AbstractActionSchedulerJob constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor ) {
		$this->action_scheduler = $action_scheduler;
		$this->monitor          = $monitor;
	}

	/**
	 * Get the base name for the job's scheduled actions.
	 *
	 * @return string
	 */
	protected function get_hook_base_name() {
		return 'automatewoo/jobs/' . $this->get_name() . '/';
	}

	/**
	 * Get the hook name for the "process item" action.
	 *
	 * This method is required by the job monitor.
	 *
	 * @return string
	 */
	public function get_process_item_hook() {
		return $this->get_hook_base_name() . 'process_item';
	}


	/**
	 * Schedules a job with recurrence. Creates a hook named like "automatewoo/jobs/{$job_name}/start
	 * recurrently. This hook is handled by the StartOnHook flow when the action is Completed.
	 *
	 * @since 6.0.0
	 *
	 * @see RecurringJobInterface
	 */
	public function schedule_recurring() {
		$interval = apply_filters( "automatewoo/intervals/{$this->get_name()}", $this->get_interval() );

		if ( ! $this->is_enabled() ) {
			$this->cancel_recurring();
			return;
		}

		if ( ! $this->get_schedule() ) {
			$this->action_scheduler->schedule_recurring_action(
				time() + $interval,
				$interval,
				$this->get_schedule_hook()
			);
		}
	}

	/**
	 * Cancels the recurring action
	 *
	 * @since 6.0.0
	 */
	public function cancel_recurring() {
		if ( $this->get_schedule() ) {
			$this->action_scheduler->cancel( $this->get_schedule_hook() );
		}
	}

	/**
	 * Check if the Job is scheduled
	 *
	 * @since 6.0.0
	 * @return int|bool The timestamp for the next occurrence of the scheduled action, true if in-progress or false if there is no scheduled action.
	 */
	public function get_schedule() {
		return $this->action_scheduler->next_scheduled_action( $this->get_schedule_hook() );
	}

	/**
	 * If a child class replaces this method and returns `false` then any existing
	 * scheduled recurring actions will be cancelled and no more will be scheduled.
	 *
	 * @since 6.0.28
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return true;
	}
}
