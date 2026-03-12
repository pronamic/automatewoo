<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;
use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Traits\ArrayValidator;
use AutomateWoo\Traits\IntegerValidator;
use AutomateWoo\Triggers\AbstractBatchedDailyTrigger;
use AutomateWoo\Triggers\BatchedWorkflowInterface;
use AutomateWoo\Workflow;
use Exception;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * BatchedWorkflows class.
 *
 * Requires a 'workflow' arg which contains the workflow ID to process items for.
 *
 * @since 5.1.0
 */
class BatchedWorkflows extends AbstractBatchedActionSchedulerJob {

	use IntegerValidator;
	use ArrayValidator;

	/**
	 * This job is allowed to run concurrently.
	 *
	 * This is because it is manually started and multiple workflows can be have job instances at the same time.
	 *
	 * @var bool
	 */
	protected $allow_concurrent = true;

	/**
	 * @var callable
	 */
	protected $get_workflow_callable;

	/**
	 * AbstractBatchedJob constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param callable                  $get_workflow
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, callable $get_workflow ) {
		$this->get_workflow_callable = $get_workflow;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'batched_workflows';
	}

	/**
	 * Handles batch creation with cursor-based pagination for built-in daily triggers.
	 *
	 * For triggers extending AbstractBatchedDailyTrigger, cursor is passed via args['_aw_cursor']
	 * to avoid offset-based pagination drift when the dataset changes between async batch runs.
	 *
	 * @since 6.2.3
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args         The args for this instance of the job.
	 *
	 * @throws Exception If an error occurs.
	 */
	public function handle_create_batch_action( int $batch_number, array $args ) {
		$this->monitor->validate_failure_rate( $this );
		$this->validate_args( $args );

		$items = $this->get_batch( $batch_number, $args );

		$process_args = $args;
		unset( $process_args['_aw_cursor'] );

		foreach ( $items as $item ) {
			$this->validate_item( $item );
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $item, $process_args ] );
		}

		if ( empty( $items ) ) {
			$this->handle_complete( $batch_number, $args );
		} else {
			$next_args = $args;

			// Only attach cursor for built-in daily triggers. Third-party triggers
			// implementing BatchedWorkflowInterface use offset-based pagination.
			$workflow = ( $this->get_workflow_callable )( $args['workflow'] );
			if ( $workflow && $workflow->get_trigger() instanceof AbstractBatchedDailyTrigger ) {
				$cursor = $this->get_cursor_from_items( $items );
				if ( $cursor > 0 ) {
					$next_args['_aw_cursor'] = $cursor;
				}
			}

			$this->schedule_create_batch_action( $batch_number + 1, $next_args );
		}
	}

	/**
	 * Get a new batch of items.
	 *
	 * Uses cursor-based pagination for built-in batched daily triggers to avoid
	 * offset drift. Falls back to offset-based pagination for third-party triggers.
	 *
	 * @since 6.2.3
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the a job cycle.
	 * @param array $args         The args for this instance of the job. Args are already validated.
	 *
	 * @return array
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function get_batch( int $batch_number, array $args ) {
		$workflow = ( $this->get_workflow_callable )( $args['workflow'] );
		$this->validate_workflow( $workflow );

		/** @var BatchedWorkflowInterface $trigger */
		$trigger = $workflow->get_trigger();

		if ( $trigger instanceof AbstractBatchedDailyTrigger && ! empty( $args['_aw_cursor'] ) ) {
			$after_id = (int) $args['_aw_cursor'];
		} else {
			$after_id = $this->get_query_offset( $batch_number );
		}

		return $trigger->get_batch_for_workflow(
			$workflow,
			$after_id,
			$this->get_batch_size()
		);
	}

	/**
	 * Extract cursor value from the last item in a batch.
	 *
	 * Assumes items are single-key associative arrays where the value is a numeric ID
	 * (e.g. ['subscription' => 123], ['customer' => 45], ['token' => 7]).
	 * This holds for all built-in AbstractBatchedDailyTrigger implementations.
	 * If a future trigger returns multi-key items, this method should be updated
	 * or the trigger should override cursor derivation.
	 *
	 * @since 6.2.3
	 *
	 * @param array $items The batch items.
	 *
	 * @return int The cursor value (last item's ID), or 0 if it cannot be derived.
	 */
	protected function get_cursor_from_items( array $items ): int {
		$last_item = end( $items );
		if ( is_array( $last_item ) ) {
			return (int) reset( $last_item );
		}
		return 0;
	}

	/**
	 * Handle a single item.
	 *
	 * @param mixed $item The item to process.
	 * @param array $args The args for this instance of the job. Args are already validated.
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function process_item( $item, array $args ) {
		$workflow = ( $this->get_workflow_callable )( $args['workflow'] );
		$this->validate_workflow( $workflow );

		/** @var BatchedWorkflowInterface $trigger */
		$trigger = $workflow->get_trigger();

		$trigger->process_item_for_workflow( $workflow, $item );
	}

	/**
	 * Validate the job args.
	 *
	 * @param array $args The args for this instance of the job.
	 *
	 * @throws InvalidArgument If args are invalid.
	 */
	protected function validate_args( array $args ) {
		if ( ! isset( $args['workflow'] ) ) {
			throw InvalidArgument::missing_required( 'workflow' );
		}

		$this->validate_positive_integer( $args['workflow'] );
	}

	/**
	 * Validate an item to be processed by the job.
	 *
	 * @param mixed $item
	 *
	 * @throws InvalidArgument If the item is not valid.
	 */
	protected function validate_item( $item ) {
		$this->validate_is_array( $item );
	}

	/**
	 * Validate the workflow.
	 *
	 * It must exist, be active and its trigger should be an instance of BatchedWorkflowInterface.
	 *
	 * @param Workflow|false $workflow
	 *
	 * @throws RuntimeException If the workflow doesn't validate correctly.
	 */
	protected function validate_workflow( $workflow ) {
		if ( ! $workflow ) {
			throw new RuntimeException( 'Error getting workflow.' );
		}

		if ( ! $workflow->is_active() ) {
			throw new RuntimeException( 'Workflow is no longer active.' );
		}

		$trigger = $workflow->get_trigger();

		if ( ! $trigger instanceof BatchedWorkflowInterface ) {
			throw new RuntimeException( 'Invalid workflow.' );
		}
	}
}
