<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Subscription_Send_Invoice
 * @since 3.5.0
 */
class Action_Subscription_Send_Invoice extends Action {

	/** @var string[] */
	public $required_data_items = [ 'subscription' ];


	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Send Invoice', 'automatewoo' );
		$this->description = __( 'Email the invoice to the subscription customer.', 'automatewoo' );
		$this->group       = __( 'Subscription', 'automatewoo' );
	}

	/**
	 * Run the action.
	 */
	public function run() {
		$subscription = $this->workflow->data_layer()->get_subscription();
		if ( ! $subscription ) {
			return;
		}

		do_action( 'woocommerce_before_resend_order_emails', $subscription, 'customer_invoice' );

		WC()->payment_gateways();
		WC()->shipping();
		WC()->mailer()->customer_invoice( $subscription );

		do_action( 'woocommerce_after_resend_order_email', $subscription, 'customer_invoice' );
	}
}
