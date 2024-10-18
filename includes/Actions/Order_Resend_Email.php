<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Order_Resend_Email
 * @since 2.2
 */
class Action_Order_Resend_Email extends Action {

	/**
	 * The data items required by the action.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'order' ];


	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Resend Order Email', 'automatewoo' );
		$this->group       = __( 'Order', 'automatewoo' );
		$this->description = __( 'Please note that email tracking is not currently supported on this action.', 'automatewoo' );
	}


	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {

		$options          = [];
		$mailer           = WC()->mailer();
		$available_emails = [
			'new_order',
			'cancelled_order',
			'failed_order',
			'customer_processing_order',
			'customer_completed_order',
			'customer_on_hold_order',
			'customer_invoice',
			'customer_refunded_order',
		];
		$mails            = $mailer->get_emails(); /** @var \WC_Email[] $mails */

		if ( $mails ) {
			foreach ( $mails as $mail ) {
				if ( in_array( $mail->id, $available_emails, true ) ) {
					$title = $mail->get_title();

					if ( ! $mail->is_customer_email() ) {
						$title .= ' - ' . __( 'Admin', 'automatewoo' );
					} else {
						$title .= ' - ' . __( 'Customer', 'automatewoo' );
					}

					$options[ $mail->id ] = $title;
				}
			}
		}

		$email = new Fields\Select( true );
		$email->set_name( 'email' );
		$email->set_title( __( 'Email', 'automatewoo' ) );
		$email->set_required();
		$email->set_options( $options );

		$this->add_field( $email );
	}

	/**
	 * Run the action.
	 *
	 * @throws \Exception When an error occurs.
	 */
	public function run() {
		$email_type = $this->get_option( 'email' );
		$order      = $this->workflow->data_layer()->get_order();

		if ( ! $email_type || ! $order ) {
			return;
		}

		do_action( 'woocommerce_before_resend_order_emails', $order );

		// Ensure gateways are loaded in case they need to insert data into the emails
		WC()->payment_gateways();
		WC()->shipping();

		// Load mailer
		$mailer = WC()->mailer();

		$mails = $mailer->get_emails();
		if ( ! $mails ) {
			return;
		}

		foreach ( $mails as $mail ) {

			if ( $mail->id !== $email_type ) {
				continue;
			}

			$mail->trigger( $order->get_id() );

			do_action( 'woocommerce_after_resend_order_email', $order, $email_type );

			// translators: %1$s Mail title, %2$d The Workflow ID
			$order->add_order_note( sprintf( __( '%1$s email notification sent by AutomateWoo workflow #%2$d', 'automatewoo' ), $mail->title, $this->workflow->get_id() ) );
		}
	}
}
