<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Trigger_Abstract_Abandoned_Cart
 */
abstract class Trigger_Abstract_Abandoned_Cart extends Trigger {


	/**
	 * Load the trigger's admin details.
	 */
	public function load_admin_details() {
		$this->description .= ' ' . sprintf(
			/* translators: %1$d number of minutes for cart timeout, %2$s documentation link start, %3$ss documentation link end. */
			__( 'Carts are considered abandoned if they are inactive for %1$d minutes. When a customer purchases or empties their abandoned cart all queued workflows will be automatically cleared. %2$sView documentation.%3$s', 'automatewoo' ),
			AW()->options()->abandoned_cart_timeout,
			'<a href="' . Admin::get_docs_link( 'abandoned-cart', 'trigger-description' ) . '" target="_blank">',
			'</a>'
		);
		$this->group = __( 'Carts', 'automatewoo' );
	}


	/**
	 * Load the trigger's fields.
	 */
	public function load_fields() {
		$this->add_field_user_pause_period();
	}


	/**
	 * Register the trigger's hooks.
	 */
	public function register_hooks() {
		add_action( 'automatewoo/cart/status_changed', [ $this, 'status_changed' ], 10, 3 );
		add_action( 'automatewoo/object/delete', [ $this, 'cart_deleted' ] );
	}


	/**
	 * @param Cart   $cart
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function status_changed( $cart, $old_status, $new_status ) {
		if ( $new_status === Cart::STATUS_ABANDONED ) {
			$this->cart_abandoned( $cart );
		} elseif ( $old_status === Cart::STATUS_ABANDONED || in_array( $new_status, Cart::get_terminal_statuses(), true ) ) {
			$this->maybe_clear_queued_emails( $cart );
		}
	}


	/**
	 * @param Cart $cart
	 */
	public function cart_abandoned( $cart ) {

		if ( ! $cart->has_items() ) {
			return;
		}

		$this->maybe_run(
			[
				'customer' => $cart->get_customer(),
				'cart'     => $cart,
			]
		);
	}


	/**
	 * @param Model|Cart $object
	 */
	public function cart_deleted( $object ) {
		if ( $object->object_type === 'cart' ) {
			$this->maybe_clear_queued_emails( $object );
		}
	}


	/**
	 * @param Cart $cart
	 */
	public function maybe_clear_queued_emails( $cart ) {
		$query = new Queue_Query();
		$query->where_workflow( $this->get_workflow_ids() );
		$query->where_cart( $cart->get_id() );

		foreach ( $query->get_results() as $event ) {
			$event->delete();
		}
	}


	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {

		$cart = $workflow->data_layer()->get_cart();

		if ( ! $cart ) {
			return false;
		}

		if ( ! $this->validate_field_user_pause_period( $workflow ) ) {
			return false;
		}

		// Run once foreach workflow for each stored cart
		// Skip the queue check because the queue should have been cleared when the cart status changed
		if ( $workflow->has_run_for_data_item( 'cart', false, true ) ) {
			return false;
		}

		return true;
	}


	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {

		if ( ! $this->validate_field_user_pause_period( $workflow ) ) {
			return false;
		}

		return true;
	}
}
