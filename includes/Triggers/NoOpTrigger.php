<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Trigger;

/**
 * Null-object trigger.
 *
 * A no-op trigger that can be used as a safe alternative to `false` when a
 * workflow has no resolvable trigger. It implements {@see TriggerInterface}
 * (via the abstract {@see Trigger}) but performs no work: it registers no
 * hooks, supplies no data items and never runs any workflows.
 *
 * Note: this is a programmatic null-object and is intentionally NOT registered
 * with the trigger registry, so it never appears in the admin UI.
 *
 * @since 6.6.0
 */
class NoOpTrigger extends Trigger {

	/**
	 * Constructor.
	 *
	 * Overrides the parent constructor so the null-object has no side effects
	 * (it does not register the `automatewoo_init_triggers` action).
	 */
	public function __construct() {
		$this->name        = 'no_op';
		$this->title       = '';
		$this->description = '';
		$this->group       = '';
	}

	/**
	 * Register hooks.
	 *
	 * Intentionally does nothing.
	 */
	public function register_hooks() {}

	/**
	 * Maybe run.
	 *
	 * Intentionally does nothing.
	 *
	 * @param \AutomateWoo\Data_Layer|array $data_layer
	 */
	public function maybe_run( $data_layer = [] ) {}

	/**
	 * Validate the workflow.
	 *
	 * A null-object trigger never represents a real trigger, so validation
	 * always fails closed.
	 *
	 * @param \AutomateWoo\Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		return false;
	}

	/**
	 * Validate before a queued event runs.
	 *
	 * A null-object trigger never represents a real trigger, so validation
	 * always fails closed.
	 *
	 * @param \AutomateWoo\Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		return false;
	}

	/**
	 * Validate the workflow's language.
	 *
	 * A null-object trigger never represents a real trigger, so validation
	 * always fails closed.
	 *
	 * @param \AutomateWoo\Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow_language( $workflow ) {
		return false;
	}
}
