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
	 * For built-in triggers extending AbstractBatchedDailyTrigger, the $offset parameter
	 * is used as a cursor: items with ID greater than this value are returned, ordered by
	 * ID ascending. For third-party triggers, it continues to function as a query offset.
	 *
	 * @param Workflow $workflow
	 * @param int      $offset The batch query offset or cursor (ID after which to fetch items).
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
