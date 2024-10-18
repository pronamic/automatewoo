<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Workflow;

/**
 * Interface BatchedWorkflowInterface
 *
 * Triggers can implement this interface to be compatible with the BatchedWorkflows job.
 *
 * @since 5.1.0
 */
interface BatchedWorkflowInterface {

	/**
	 * Get a batch of items to process for given workflow.
	 *
	 * @param Workflow $workflow
	 * @param int      $offset The batch query offset.
	 * @param int      $limit  The max items for the query.
	 *
	 * @return array[] Array of items in array format. Items will be stored in the database so they should be IDs not objects.
	 */
	public function get_batch_for_workflow( Workflow $workflow, int $offset, int $limit ): array;

	/**
	 * Process a single item for a workflow to process.
	 *
	 * @param Workflow $workflow
	 * @param array    $item
	 */
	public function process_item_for_workflow( Workflow $workflow, array $item );
}
