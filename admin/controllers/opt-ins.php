<?php
// phpcs:ignoreFile

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo\Admin;
use AutomateWoo\Clean;
use AutomateWoo\Options;
use AutomateWoo\Customer_Factory;
use AutomateWoo\Report_Optins;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Unsubscribes
 */
class Optins extends Base {


	function handle() {

		$action = $this->get_current_action();

		switch ( $action ) {

			case 'bulk_optout':
			case 'bulk_optin':
				$this->action_bulk_edit( str_replace( 'bulk_', '', $action ) );
				$this->output_list_table();
				break;

			default:
				$this->output_list_table();
				break;
		}
	}


	private function output_list_table() {
		$table = new Report_Optins();
		$table->prepare_items();
		$table->nonce_action = $this->get_nonce_action();

		$this->heading_links = [
			Admin::page_url( Options::optin_enabled() ? 'tool-optin-importer' : 'tool-optout-importer' ) => __( 'Import', 'automatewoo' )
		];

		if ( Options::optin_enabled() ) {
			$sidebar_content = __( 'Your store is set to require customers to opt-in before non-transactional workflows will run for them.', 'automatewoo' );
		}
		else {
			$sidebar_content = __( 'Your store is set to automatically opt-in customers for workflows but they can opt-out with the unsubscribe link in emails and SMS.', 'automatewoo' );
		}

		$sidebar_content .= ' ' . sprintf(
			__( 'More information on opt-ins and opt-outs is available <%s>in the documentation.<%s>', 'automatewoo' ),
			'a href="' . Admin::get_docs_link('unsubscribes', 'unsubscribes-list' ) . '" target="_blank"',
			'/a'
		);

		$this->output_view( 'page-table-with-sidebar', [
			'table' => $table,
			'sidebar_content' => '<p>' . $sidebar_content . '</p>'
		]);
	}


	/**
	 * @param $action
	 */
	private function action_bulk_edit( $action ) {

		$this->verify_nonce_action();

		$ids = Clean::ids( aw_request( 'customer_ids' ) );

		if ( empty( $ids ) ) {
			$this->add_error( __( 'Please select some items to bulk edit.', 'automatewoo') );
			return;
		}

		foreach ( $ids as $id ) {
			if ( ! $customer = Customer_Factory::get( $id ) ) {
				continue;
			}

			switch ( $action ) {
				case 'optin':
					$customer->opt_in();
					break;
				case 'optout':
					$customer->opt_out();
					break;
			}
		}

		$this->add_message( __( 'Bulk edit completed.', 'automatewoo' ) );
	}
}

return new Optins();
