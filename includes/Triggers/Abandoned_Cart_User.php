<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Trigger_Abandoned_Cart_User
 */
class Trigger_Abandoned_Cart_User extends Trigger_Abstract_Abandoned_Cart {

	/** @var array */
	public $supplied_data_items = [ 'cart', 'customer' ];


	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Cart Abandoned - Registered Users Only', 'automatewoo' );
		$this->description = __( 'This trigger fires when a cart belonging to a registered customer is abandoned.', 'automatewoo' );
		parent::load_admin_details();
	}


	/**
	 * @param Cart $cart
	 */
	public function cart_abandoned( $cart ) {
		if ( $cart->get_user_id() ) {
			parent::cart_abandoned( $cart );
		}
	}


	/**
	 * @param Cart $cart
	 */
	public function maybe_clear_queued_emails( $cart ) {
		if ( $cart->get_user_id() ) {
			parent::maybe_clear_queued_emails( $cart );
		}
	}


	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		$customer = $workflow->data_layer()->get_customer();
		$cart     = $workflow->data_layer()->get_cart();

		if ( ! $customer || ! $cart ) {
			return false;
		}

		if ( ! $cart->get_user_id() ) {
			return false;
		}

		return parent::validate_workflow( $workflow );
	}
}
