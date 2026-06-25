<?php

namespace AutomateWoo;

use AutomateWoo\Workflows\Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Tool_Reset_Workflow_Records
 */
class Tool_Reset_Workflow_Records extends Tool_Abstract {

	/**
	 * @var string
	 */
	public $id = 'reset_workflow_records';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->title       = __( 'Reset Workflow Records', 'automatewoo' );
		$this->description = __( 'Deletes all logs, queued events and conversions for a workflow. The workflow itself will not be deleted.', 'automatewoo' );
	}


	/**
	 * Get the tool's form fields.
	 *
	 * @return array
	 */
	public function get_form_fields() {

		$fields = [];

		$fields[] = ( new Fields\Workflow() )
			->set_name_base( 'args' )
			->add_extra_attr( 'data-aw-tool', $this->id );

		return $fields;
	}


	/**
	 * @param array $args sanitized
	 * @return bool|\WP_Error
	 */
	public function validate_process( $args ) {

		if ( empty( $args['workflow'] ) ) {
			return new \WP_Error( 1, __( 'Please select a workflow to reset.', 'automatewoo' ) );
		}

		$workflow = Factory::get( $args['workflow'] );
		if ( ! $workflow ) {
			return false;
		}

		return true;
	}



	/**
	 * Do validation in the validate_process() method not here
	 *
	 * @param array $args
	 */
	public function display_confirmation_screen( $args ) {

		$args = $this->sanitize_args( $args );

		$workflow = Factory::get( $args['workflow'] );

		echo wp_kses_post(
			'<p>' . sprintf(
				/* translators: %s Workflow title. */
				__( 'Are you sure you want to reset all records for the workflow <strong>%s</strong>? This can not be undone.', 'automatewoo' ),
				$workflow->title
			) . '</p>'
		);
	}



	/**
	 * @param array $args
	 * @return bool|\WP_Error
	 */
	public function process( $args ) {

		$args = $this->sanitize_args( $args );

		$workflow = Factory::get( $args['workflow'] );

		$queries = [ 'AutomateWoo\Log_Query', 'AutomateWoo\Queue_Query' ];

		foreach ( $queries as $class ) {

			/** @var Query_Abstract $query */
			$query = new $class();
			$query->where( 'workflow_id', $workflow->get_id() );

			$results = $query->get_results();

			foreach ( $results as $result ) {
				$result->delete();
			}
		}

		return true;
	}
}
