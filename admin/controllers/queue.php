<?php
// phpcs:ignoreFile

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo\Admin;
use AutomateWoo\Clean;
use AutomateWoo\Logger;
use AutomateWoo\Queue_Manager;
use AutomateWoo\Queued_Event_Factory;
use AutomateWoo\Report_Queue;
use AutomateWoo\Jobs\JobException;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Queue
 */
class Queue extends Base {


	function handle() {

		$action = $this->get_current_action();

		switch ( $action ) {

			case 'run_now':
				$this->action_run_now();
				$this->output_list_table();
				break;

			case 'bulk_delete':
				$this->action_bulk_edit( str_replace( 'bulk_', '', $action ) );
				$this->output_list_table();
				break;

			default:
				$this->output_list_table();
				break;
		}
	}


	private function output_list_table() {
		$table = new Report_Queue();
		$table->prepare_items();
		$table->nonce_action = $this->get_nonce_action();

		$sidebar_content = '<p>' . sprintf(
				__( 'Workflows that are not set to run immediately will be added to this queue. The queue is checked every two minutes so actual run times will vary slightly. <%s>Read more&hellip;<%s>', 'automatewoo' ),
				'a href="' . Admin::get_docs_link( 'queue', 'queue-list' ) . '" target="_blank"',
				'/a'
			) . '</p>';

		try {
			$deletion_job = AW()->job_service()->get_job( 'delete_failed_queued_workflows' );
			$sidebar_content .= '<p>' . sprintf( __( 'Failed queue items will be deleted after %d days', 'automatewoo' ), $deletion_job->get_deletion_period() ) . '</p>';
		} catch ( JobException $e ) {
			Logger::error( 'jobs', $e->getMessage() );
		}

		$this->output_view( 'page-table-with-sidebar', [
			'table' => $table,
			'sidebar_content' => $sidebar_content
		]);
	}


	/**
	 * Run a single queued event
	 */
	private function action_run_now() {

		$this->verify_nonce_action();

		$queued_event = Queued_Event_Factory::get( absint( aw_request( 'queued_event_id' ) ) );

		if ( ! $queued_event ) {
			return;
		}

		if ( $queued_event->run() ) {
			$this->add_message( __( 'Queued event run successfully.', 'automatewoo' ) );
		}
		else {
			$message = $queued_event->get_failure_message();
			$this->add_error( __( 'Queued event failed to run.', 'automatewoo'), $message );
		}
	}


	/**
	 * @param $action
	 */
	private function action_bulk_edit( $action ) {

		$this->verify_nonce_action();

		$ids = Clean::ids( aw_request( 'queued_event_ids' ) );

		if ( empty( $ids ) ) {
			$this->add_error( __( 'Please select some queued events to bulk edit.', 'automatewoo') );
			return;
		}

		foreach ( $ids as $id ) {

			$queued_event = Queued_Event_Factory::get( $id );

			if ( ! $queued_event )
				continue;

			switch ( $action ) {
				case 'delete':
					$queued_event->delete();
					break;
			}
		}

		$this->add_message( __( 'Bulk edit completed.', 'automatewoo' ) );
	}
}

return new Queue();
