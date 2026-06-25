<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Change_Workflow_Status
 */
class Action_Change_Workflow_Status extends Action {

	/**
	 * @var array
	 */
	public $required_data_items = [ 'workflow' ];


	/**
	 * Method to set the action's admin props.
	 */
	public function load_admin_details() {
		$this->title = __( 'Change Workflow Status', 'automatewoo' );
		$this->group = __( 'AutomateWoo', 'automatewoo' );
	}


	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {
		$status = ( new Fields\Select( false ) )
			->set_name( 'status' )
			->set_title( __( 'Status', 'automatewoo' ) )
			->set_options(
				[
					'publish'     => __( 'Active', 'automatewoo' ),
					'aw-disabled' => __( 'Disabled', 'automatewoo' ),
				]
			)
			->set_required();

		$this->add_field( $status );
	}


	/**
	 * Run the action.
	 */
	public function run() {
		$workflow = $this->workflow->data_layer()->get_workflow();
		$status   = $this->get_option( 'status' );

		if ( ! $status || ! $workflow ) {
			return;
		}

		$workflow->update_status( $status );
	}
}
