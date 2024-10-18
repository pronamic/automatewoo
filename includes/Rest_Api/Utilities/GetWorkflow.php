<?php

namespace AutomateWoo\Rest_Api\Utilities;

use AutomateWoo\Workflow;
use AutomateWoo\Workflows\Factory;


/**
 * Trait GetWorkflow
 *
 * @since   5.0.0
 * @package AutomateWoo\Rest_Api\Utilities
 */
trait GetWorkflow {

	/**
	 * Get a workflow by ID.
	 *
	 * @param int $id The workflow ID.
	 *
	 * @return Workflow The workflow object.
	 * @throws RestException When the workflow does not exist.
	 */
	protected function get_workflow( $id ) {
		$workflow = Factory::get( $id );
		if ( $workflow === false ) {
			throw new RestException(
				'rest_invalid_workflow_id',
				esc_html__( 'Invalid workflow ID.', 'automatewoo' ),
				404
			);
		}

		return $workflow;
	}
}
