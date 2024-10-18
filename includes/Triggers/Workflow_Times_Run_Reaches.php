<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Trigger_Workflow_Times_Run_Reaches
 */
class Trigger_Workflow_Times_Run_Reaches extends Trigger {

	public $supplied_data_items = [ 'workflow' ];


	function load_admin_details() {
		$this->title = __('Workflow Times Run Reaches', 'automatewoo');
		$this->group = __('Workflows', 'automatewoo');
	}


	/**
	 * Add options to the trigger
	 */
	function load_fields() {
		$workflow_field = new Fields\Workflow();

		$times_run = new Fields\Number();
		$times_run->set_name('times_run');
		$times_run->set_title(__('Times run', 'automatewoo') );

		$this->add_field( $workflow_field );
		$this->add_field( $times_run );
	}



	/**
	 * When could this trigger run?
	 */
	function register_hooks() {
		add_action( 'automatewoo_after_workflow_run', [ $this, 'catch_hooks' ] );
	}


	/**
	 * Route hooks through here
	 *
	 * @param $workflow Workflow
	 */
	function catch_hooks( $workflow ) {
		$this->maybe_run([
			'workflow' => $workflow,
			'post' => $workflow->post
		]);
	}


	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	function validate_workflow( $workflow ) {

		$workflow_data_item = $workflow->data_layer()->get_workflow();

		$selected_workflow_id = $workflow->get_trigger_option( 'workflow' );
		$times_run = (int) $workflow->get_trigger_option('times_run');

		if ( ! $workflow_data_item )
			return false;

		// match running workflow to selected workflow
		if ( $workflow_data_item->get_id() != $selected_workflow_id )
			return false;

		if ( $workflow_data_item->get_times_run() !== $times_run )
			return false;

		return true;
	}

}
