<?php
// phpcs:ignoreFile

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo\Clean;
use AutomateWoo\Customer_Factory;
use AutomateWoo\Guest_Factory;
use AutomateWoo\Report_Guests;
use AutomateWoo\Guest;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Guests
 */
class Guests extends Base {


	function handle() {

		$action = $this->get_current_action();

		switch ( $action ) {

			case 'view':
				$this->heading = __( 'Guest details', 'automatewoo' );
				$this->output_view_single();
				break;

			case 'delete':
				$this->action_delete();
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


	function output_view_single() {

		if ( ! $guest = $this->get_current_guest() ) {
			$this->output_no_guest();
		}

		$this->output_view( 'page-guest-details', [
			'guest' => $guest,
			'customer' => Customer_Factory::get_by_guest_id( $guest->get_id() )
		] );
	}


	function output_no_guest() {
		wp_die( __( 'Missing guest data.', 'automatewoo' ) );
	}


	protected function output_list_table() {
		$table = new Report_Guests();
		$table->prepare_items();
		$table->nonce_action = $this->get_nonce_action();

		$sidebar_content = '<p>' .
			__( 'Guest data is stored here when a customer places an order but does not create an account. '
			. 'Or, if you have enabled pre-submit capturing, guest data can also be captured from a form on your website before an order is placed.', 'automatewoo' )
			. '</p>';

		$this->output_view( 'page-table-with-sidebar', [
			'table' => $table,
			'sidebar_content' => $sidebar_content
		]);
	}


	/**
	 * @param $action
	 */
	protected function action_bulk_edit( $action ) {

		$this->verify_nonce_action();

		$ids = Clean::ids( aw_request( 'guest_ids' ) );

		if ( empty( $ids ) ) {
			$this->add_error( __( 'Please select some guests to bulk edit.', 'automatewoo' ) );
			return;
		}

		foreach ( $ids as $id ) {

			if ( ! $guest = Guest_Factory::get( $id ) ) {
				continue;
			}

			switch ( $action ) {
				case 'delete':
					$guest->delete();
					break;
			}
		}

		$this->add_message( __( 'Bulk edit completed.', 'automatewoo' ) );
	}


	/**
	 * Delete guest action
	 */
	protected function action_delete() {

		$this->verify_nonce_action();

		if ( ! $guest = $this->get_current_guest() ) {
			$this->output_no_guest();
		}

		$guest->delete();

		$this->add_message( __( 'Guest successfully deleted.', 'automatewoo' ) );

		$this->redirect_after_action();
	}


	/**
	 * @return Guest|false
	 */
	function get_current_guest() {
		return Guest_Factory::get( Clean::id( aw_request( 'guest_id' ) ) );
	}

}

return new Guests();
