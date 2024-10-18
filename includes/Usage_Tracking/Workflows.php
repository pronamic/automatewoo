<?php

namespace AutomateWoo\Usage_Tracking;

use AutomateWoo\Workflow;
use AutomateWoo\Workflows\Factory;

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track the usage of Workflows.
 *
 * @package AutomateWoo\Usage_Tracking
 * @since   4.9.0
 */
class Workflows implements Event_Tracker_Interface {

	use Event_Helper;
	use WorkflowTracksData;

	/**
	 * Initialize the tracking class with various hooks.
	 */
	public function init() {
		add_action( 'automatewoo/workflow/before_run', [ $this, 'before_run' ] );
		add_action( 'automatewoo/workflow/created', [ $this, 'created' ] );
	}

	/**
	 * Record workflow data before a workflow runs.
	 *
	 * @param Workflow $workflow The workflow that is running.
	 */
	public function before_run( Workflow $workflow ) {
		$this->record_event( 'workflow_before_run', $this->get_workflow_data( $workflow ) );
	}

	/**
	 * Record workflow data when a workflow is created.
	 *
	 * @param int $workflow_id The workflow ID.
	 */
	public function created( $workflow_id ) {
		$workflow = Factory::get( $workflow_id );
		if ( ! $workflow instanceof Workflow ) {
			return;
		}

		$this->record_event( 'workflow_created', $this->get_workflow_data( $workflow ) );
	}
}
