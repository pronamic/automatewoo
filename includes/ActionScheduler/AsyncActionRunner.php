<?php

namespace AutomateWoo\ActionScheduler;

use ActionScheduler_Lock;

defined( 'ABSPATH' ) || exit;

/**
 * Class AsyncActionRunner
 *
 * @since 5.2.0
 */
class AsyncActionRunner {

	/**
	 * Whether the shutdown hook has been attached.
	 *
	 * @var bool
	 */
	protected $has_attached_shutdown_hook = false;

	/**
	 * @var QueueRunnerAsyncRequest
	 */
	protected $async_request;

	/**
	 * @var ActionScheduler_Lock
	 */
	protected $locker;

	/**
	 * AsyncActionRunner constructor.
	 *
	 * @param AW_AsyncRequest_QueueRunner $async_request
	 * @param ActionScheduler_Lock        $locker
	 */
	public function __construct( AW_AsyncRequest_QueueRunner $async_request, ActionScheduler_Lock $locker ) {
		$this->async_request = $async_request;
		$this->locker        = $locker;
	}

	/**
	 * Attach async runner shutdown hook before ActionScheduler shutdown hook.
	 *
	 * The shutdown hook should only be attached if an async event has been created in the current request.
	 * The hook is only attached if it hasn't already been attached.
	 *
	 * @see ActionScheduler_QueueRunner::hook_dispatch_async_request
	 */
	public function attach_shutdown_hook() {
		if ( $this->has_attached_shutdown_hook ) {
			return;
		}

		$this->has_attached_shutdown_hook = true;
		add_action( 'shutdown', [ $this, 'maybe_dispatch_async_request' ], 9 );
	}

	/**
	 * Dispatches an async queue runner if the following conditions are met:
	 *
	 * - Not running in the admin context (ActionScheduler will dispatch a queue runner in that instance)
	 * - The filter `automatewoo_disable_async_runner` doesn't return true
	 * - The async runner is not currently locked
	 */
	public function maybe_dispatch_async_request() {
		if ( is_admin() ) {
			// ActionScheduler will dispatch an async runner request on it's own.
			return;
		}

		if ( apply_filters( 'automatewoo_disable_async_runner', false ) ) {
			return;
		}

		if ( $this->locker->is_locked( 'async-request-runner' ) ) {
			// An async runner request has already occurred in the last 60 seconds.
			return;
		}

		$this->locker->set( 'async-request-runner' );
		$this->async_request->maybe_dispatch();
	}
}
