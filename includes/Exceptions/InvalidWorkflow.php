<?php

namespace AutomateWoo\Exceptions;

use RuntimeException;

/**
 * Class InvalidWorkflow
 *
 * @since 5.1.0
 */
class InvalidWorkflow extends RuntimeException implements Exception {

	/**
	 * Create a new exception when a given workflow ID already exists.
	 *
	 * @param string|int $id
	 *
	 * @return static
	 */
	public static function workflow_exists( $id ): InvalidWorkflow {
		return new static( sprintf( 'The workflow with ID "%s" already exists.', $id ) );
	}

	/**
	 * Create a new exception when there is an issue creating a new workflow.
	 *
	 * @param string $error
	 *
	 * @return InvalidWorkflow
	 */
	public static function error_creating_workflow( $error ): InvalidWorkflow {
		return new static( sprintf( 'There was an error creating the workflow: "%s"', $error ) );
	}

	/**
	 * Create a new exception when there is an issue updating an existing workflow.
	 *
	 * @param string $error
	 *
	 * @return InvalidWorkflow
	 */
	public static function error_updating_workflow( $error ): InvalidWorkflow {
		return new static( sprintf( 'There was an error updating the workflow: "%s"', $error ) );
	}
}
