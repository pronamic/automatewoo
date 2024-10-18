<?php

namespace AutomateWoo\ActionScheduler;

defined( 'ABSPATH' ) || exit;

/**
 * ActionScheduler service class.
 *
 * Acts as a wrapper for ActionScheduler's public functions.
 *
 * @since 5.1.0
 */
class ActionScheduler implements ActionSchedulerInterface {

	/**
	 * @var AsyncActionRunner
	 */
	protected $async_runner;

	/**
	 * List of async actions to enqueue on shutdown.
	 *
	 * @var array[]
	 */
	protected $shutdown_async_actions = [];

	/**
	 * ActionScheduler constructor.
	 *
	 * @param AsyncActionRunner $async_runner
	 */
	public function __construct( AsyncActionRunner $async_runner ) {
		$this->async_runner = $async_runner;

		add_action( 'shutdown', [ $this, 'enqueue_shutdown_actions' ], 5 );
	}

	/**
	 * Schedule an action to run once at some time in the future
	 *
	 * @param int    $timestamp When the job will run.
	 * @param string $hook      The hook to trigger.
	 * @param array  $args      Arguments to pass when the hook triggers.
	 * @param string $group     The group to assign this job to.
	 *
	 * @return string The action ID.
	 */
	public function schedule_single( $timestamp, $hook, $args = [], $group = 'automatewoo' ) {
		return as_schedule_single_action( $timestamp, $hook, $args, $group );
	}

	/**
	 * Schedule an action to run now i.e. in the next available batch.
	 *
	 * This differs from async actions by having a scheduled time rather than being set for '0000-00-00 00:00:00'.
	 * We could use an async action instead but they can't be viewed easily in the admin area
	 * because the table is sorted by schedule date.
	 *
	 * @since 5.2.0
	 *
	 * @param string $hook     The hook to trigger.
	 * @param array  $args     Arguments to pass when the hook triggers.
	 * @param string $group    The group to assign this job to.
	 * @param int    $priority The priority of the scheduled action.
	 *
	 * @return string The action ID.
	 */
	public function schedule_immediate( string $hook, $args = [], $group = 'automatewoo', $priority = 10 ) {
		return as_schedule_single_action( gmdate( 'U' ) - 1, $hook, $args, $group, false, $priority );
	}

	/**
	 * Schedule a recurring action
	 *
	 * @since 5.8.1
	 *
	 * @param int    $timestamp When the first instance of the job will run.
	 * @param int    $interval_in_seconds How long to wait between runs.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param bool   $unique Whether the action should be unique.
	 *
	 * @return int The action ID.
	 */
	public function schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = 'automatewoo', $unique = true ) {
		return as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args, $group, $unique );
	}

	/**
	 * Enqueue an action to run one time, as soon as possible
	 *
	 * @param string $hook  The hook to trigger.
	 * @param array  $args  Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to. Defaults to 'automatewoo'.
	 *
	 * @return int The action ID.
	 */
	public function enqueue_async_action( $hook, $args = [], $group = 'automatewoo' ) {
		$this->async_runner->attach_shutdown_hook();
		return $this->schedule_immediate( $hook, $args, $group, 0 );
	}

	/**
	 * Enqueue an action to run one time, as soon as possible, BUT the action is not created until 'shutdown' or
	 * when this request is finished.
	 *
	 * This is useful to avoid cases where Action Scheduler is already running in the background and runs an action
	 * before the current request is finished.
	 *
	 * @since 5.5.5
	 *
	 * @param string $hook  The hook to trigger.
	 * @param array  $args  Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to. Defaults to 'automatewoo'.
	 */
	public function enqueue_async_action_on_shutdown( $hook, $args = [], $group = 'automatewoo' ) {
		$this->shutdown_async_actions[] = [ $hook, $args, $group ];
	}

	/**
	 * Check if there is an existing action in the queue with a given hook, args and group combination.
	 *
	 * An action in the queue could be pending, in-progress or async. If the is pending for a time in
	 * future, its scheduled date will be returned as a timestamp. If it is currently being run, or an
	 * async action sitting in the queue waiting to be processed, in which case boolean true will be
	 * returned. Or there may be no async, in-progress or pending action for this hook, in which case,
	 * boolean false will be the return value.
	 *
	 * @param string $hook
	 * @param array  $args
	 * @param string $group The group to check for jobs. Defaults to 'automatewoo'.
	 *
	 * @return int|bool The timestamp for the next occurrence of a pending scheduled action, true for an async or in-progress action or false if there is no matching action.
	 */
	public function next_scheduled_action( $hook, $args = null, $group = 'automatewoo' ) {
		return as_next_scheduled_action( $hook, $args, $group );
	}

	/**
	 * Search for scheduled actions.
	 *
	 * @param array  $args          See as_get_scheduled_actions() for possible arguments.
	 * @param string $return_format OBJECT, ARRAY_A, or ids.
	 * @param string $group         The group to search for jobs. Defaults to 'automatewoo'.
	 *
	 * @return array
	 */
	public function search( $args = [], $return_format = OBJECT, $group = 'automatewoo' ) {
		$args['group'] = $group;

		return as_get_scheduled_actions( $args, $return_format );
	}

	/**
	 * Cancel the next scheduled instance of an action with a matching hook (and optionally matching args and group).
	 *
	 * Any recurring actions with a matching hook should also be cancelled, not just the next scheduled action.
	 *
	 * @param string $hook  The hook that the job will trigger.
	 * @param array  $args  Args that would have been passed to the job.
	 * @param string $group The group the job is assigned to. Defaults to 'automatewoo'.
	 *
	 * @return string|null The scheduled action ID if a scheduled action was found, or null if no matching action found.
	 */
	public function cancel( string $hook, $args = [], $group = 'automatewoo' ) {
		return as_unschedule_action( $hook, $args, $group );
	}

	/**
	 * Enqueue shutdown actions if there are any.
	 *
	 * @since 5.5.5
	 */
	public function enqueue_shutdown_actions() {
		foreach ( $this->shutdown_async_actions as $action ) {
			$this->enqueue_async_action( ...$action );
		}
	}
}
