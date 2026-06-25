<?php

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo\Cart_Factory;
use AutomateWoo\Cart;
use AutomateWoo\Clean;
use AutomateWoo\Report_Carts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Carts
 */
class Carts extends Base {


	/**
	 * Handle the carts page output and any bulk actions.
	 */
	public function handle() {

		$action = $this->get_current_action();

		switch ( $action ) {
			case 'bulk_delete':
			case 'bulk_mark_active':
			case 'bulk_mark_abandoned':
			case 'bulk_mark_emptied':
			case 'bulk_mark_placed':
			case 'bulk_mark_recovered':
				$this->action_bulk_edit( str_replace( 'bulk_', '', $action ) );
				$this->output_list_table();
				break;

			default:
				$this->output_list_table();
				break;
		}
	}


	/**
	 * Output the carts list table.
	 */
	private function output_list_table() {
		$table = new Report_Carts();
		$table->prepare_items();
		$table->nonce_action = $this->get_nonce_action();

		$sidebar_content = '<p>' .
			sprintf(
				/* translators: %s: Amount of days after which carts will be deleted. */
				__( 'Stored carts are shown here, including active and abandoned carts as well as carts that were emptied, placed or recovered. Carts are automatically deleted %s days after their last update.', 'automatewoo' ),
				AW()->options()->clear_inactive_carts_after
			)
			. '</p>';

		$this->output_view(
			'page-table-with-sidebar',
			[
				'table'           => $table,
				'sidebar_content' => $sidebar_content,
			]
		);
	}


	/**
	 * @param string $action
	 */
	private function action_bulk_edit( $action ) {

		$this->verify_nonce_action();

		$ids = Clean::ids( aw_request( 'cart_ids' ) );

		if ( empty( $ids ) ) {
			$this->add_error( __( 'Please select some carts to bulk edit.', 'automatewoo' ) );
			return;
		}

		$skipped             = 0;
		$activated_customers = [];

		foreach ( $ids as $id ) {

			$cart = Cart_Factory::get( $id );
			if ( ! $cart ) {
				continue;
			}

			switch ( $action ) {
				case 'mark_active':
					// Keep the one-current-cart-per-customer invariant: don't revive a
					// historical cart to active when the customer already has a current
					// (active/abandoned) cart, or when one was already activated for them
					// earlier in this batch. Otherwise tracking and abandoned-cart
					// workflows could run twice for the same customer.
					if ( ! $this->mark_cart_active_without_duplicates( $cart, $activated_customers ) ) {
						++$skipped;
					}
					break;
				case 'mark_abandoned':
					// Don't push terminal carts (placed, recovered, emptied) back to
					// abandoned: that fires abandoned-cart workflows for carts that were
					// already purchased or cleared.
					if ( $cart->is_current() ) {
						$cart->update_status( Cart::STATUS_ABANDONED );
					} else {
						++$skipped;
					}
					break;
				case 'mark_emptied':
					$cart->update_status( Cart::STATUS_EMPTIED );
					break;
				case 'mark_placed':
					$cart->update_status( Cart::STATUS_PLACED );
					break;
				case 'mark_recovered':
					$cart->update_status( Cart::STATUS_RECOVERED );
					break;
				case 'delete':
					$cart->delete();
					break;
			}
		}

		if ( $skipped > 0 ) {
			if ( 'mark_abandoned' === $action ) {
				/* translators: %d: number of carts skipped during the bulk edit. */
				$skipped_message = _n(
					'Bulk edit completed. %d cart was skipped because it was already purchased or cleared.',
					'Bulk edit completed. %d carts were skipped because they were already purchased or cleared.',
					$skipped,
					'automatewoo'
				);
			} else {
				/* translators: %d: number of carts skipped during the bulk edit. */
				$skipped_message = _n(
					'Bulk edit completed. %d cart was skipped to avoid creating a duplicate active cart.',
					'Bulk edit completed. %d carts were skipped to avoid creating duplicate active carts.',
					$skipped,
					'automatewoo'
				);
			}

			$this->add_message( sprintf( $skipped_message, $skipped ) );
		} else {
			$this->add_message( __( 'Bulk edit completed.', 'automatewoo' ) );
		}
	}

	/**
	 * Mark a cart active without breaking the one-current-cart-per-customer invariant.
	 *
	 * Skips the cart when the customer already has a different current cart (in the
	 * database or activated earlier in the same batch), so bulk "mark active" cannot
	 * leave several active rows for one customer.
	 *
	 * @param Cart  $cart
	 * @param array $activated_customers Customer keys already activated in this batch, passed by reference.
	 *
	 * @return bool True if the cart was marked active, false if it was skipped.
	 */
	private function mark_cart_active_without_duplicates( $cart, array &$activated_customers ) {
		$user_id  = $cart->get_user_id();
		$guest_id = $cart->get_guest_id();

		if ( $user_id ) {
			$customer_key = 'u:' . $user_id;
		} elseif ( $guest_id ) {
			$customer_key = 'g:' . $guest_id;
		} else {
			$customer_key = '';
		}

		if ( $customer_key ) {
			if ( isset( $activated_customers[ $customer_key ] ) ) {
				return false;
			}

			$existing = $user_id
				? Cart_Factory::get_by_user_id( $user_id )
				: Cart_Factory::get_by_guest_id( $guest_id );

			if ( $existing && $existing->get_id() !== $cart->get_id() ) {
				return false;
			}
		}

		$cart->update_status( Cart::STATUS_ACTIVE );

		if ( $customer_key ) {
			$activated_customers[ $customer_key ] = true;
		}

		return true;
	}
}

return new Carts();
