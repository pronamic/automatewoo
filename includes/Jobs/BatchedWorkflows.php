<?php

namespace AutomateWoo\Jobs;

use AutomateWoo\ActionScheduler\ActionSchedulerInterface;
use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Traits\ArrayValidator;
use AutomateWoo\Traits\IntegerValidator;
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
	 * Get a new batch of items.
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

		return $trigger->get_batch_for_workflow(
			$workflow,
			$this->get_query_offset( $batch_number ),
			$this->get_batch_size()
		);
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
