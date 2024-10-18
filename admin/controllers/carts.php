<?php
// phpcs:ignoreFile

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo\Cart_Factory;
use AutomateWoo\Clean;
use AutomateWoo\Report_Carts;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Carts
 */
class Carts extends Base {


	function handle() {

		$action = $this->get_current_action();

		switch ( $action ) {
			case 'bulk_delete':
			case 'bulk_mark_active':
			case 'bulk_mark_abandoned':
				$this->action_bulk_edit( str_replace( 'bulk_', '', $action ) );
				$this->output_list_table();
				break;

			default:
				$this->output_list_table();
				break;
		}
	}


	private function output_list_table() {
		$table = new Report_Carts();
		$table->prepare_items();
		$table->nonce_action = $this->get_nonce_action();

		$sidebar_content = '<p>' .
			sprintf(
				__( 'Currently active carts are shown here which includes any cart that has not been cleared at purchase or emptied by its owner. Carts are automatically deleted %s days after their last update.', 'automatewoo' ),
				AW()->options()->clear_inactive_carts_after
			)
			. '</p>';

		$this->output_view( 'page-table-with-sidebar', [
			'table' => $table,
			'sidebar_content' => $sidebar_content
		]);
	}


	/**
	 * @param $action
	 */
	private function action_bulk_edit( $action ) {

		$this->verify_nonce_action();

		$ids = Clean::ids( aw_request( 'cart_ids' ) );

		if ( empty( $ids ) ) {
			$this->add_error( __( 'Please select some carts to bulk edit.', 'automatewoo') );
			return;
		}

		foreach ( $ids as $id ) {

			if ( ! $cart = Cart_Factory::get( $id ) ) {
				continue;
			}

			switch ( $action ) {
				case 'mark_active':
					$cart->update_status( 'active' );
					break;
				case 'mark_abandoned':
					$cart->update_status( 'abandoned' );
					break;
				case 'delete':
					$cart->delete();
					break;
			}
		}

		$this->add_message( __( 'Bulk edit completed.', 'automatewoo' ) );
	}
}

return new Carts();