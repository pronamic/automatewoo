<?php
// phpcs:ignoreFile

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo\Clean;
use AutomateWoo\Log_Factory;
use AutomateWoo\Report_Logs;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Logs
 */
class Logs extends Base {


	function handle() {

		$action = $this->get_current_action();

		switch ( $action ) {
			case 'rerun':
				$this->action_rerun();
				break;

			case 'bulk_delete':
				$this->action_bulk_edit( str_replace( 'bulk_', '', $action ) );
				$this->output_list_table();
				break;

			default:
				if ( aw_request( 'did-action' ) ) {
					$this->load_stored_responses();
				}
				$this->output_list_table();
				break;
		}
	}


	private function output_list_table() {
		$table = new Report_Logs();
		$table->prepare_items();
		$table->nonce_action = $this->get_nonce_action();

		$sidebar_content = '<p>' . __( 'Every time a workflow runs a log entry is created. Logs are used by some triggers to determine when they should and should not fire. For this reason deleting logs should generally be avoided.', 'automatewoo' ) . '</p>';

		$this->output_view( 'page-table-with-sidebar', [
			'table' => $table,
			'sidebar_content' => $sidebar_content
		]);
	}


	private function action_rerun() {

		$this->verify_nonce_action( 'rerun_log' );

		$log = Log_Factory::get( Clean::id( aw_request( 'log_id' ) ) );

		if ( $log ) {
			$new_log = $log->rerun();

			if ( $new_log ) {
				$this->add_message(
					__( 'Workflow was successfully re-run.', 'automatewoo' ),
					sprintf( __( 'Created log #%d.', 'automatewoo' ), $new_log->get_id() )
				);
			}
			else {
				$this->add_error( __( 'Workflow could not be re-run.', 'automatewoo' ) );
			}
		}
		else {
			$this->add_error( __( 'Log not found.', 'automatewoo' ) );
		}

		$this->redirect_after_action();
	}


	/**
	 * @param $action
	 */
	private function action_bulk_edit( $action ) {

		$this->verify_nonce_action();

		$ids = Clean::ids( aw_request( 'log_ids' ) );

		if ( empty( $ids ) ) {
			$this->add_error( __('Please select some logs to bulk edit.', 'automatewoo') );
			return;
		}

		foreach ( $ids as $id ) {

			if ( ! $log = Log_Factory::get( $id ) ) {
				continue;
			}

			switch ( $action ) {
				case 'delete':
					$log->delete();
					break;
			}
		}

		$this->add_message( __('Bulk edit completed.', 'automatewoo' ) );
	}
}

return new Logs();
