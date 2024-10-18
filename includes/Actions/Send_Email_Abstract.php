<?php

namespace AutomateWoo;

use AutomateWoo\Actions\PreviewableInterface;
use AutomateWoo\Actions\TestableInterface;
use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Fields\EmailAddressWithName;
use AutomateWoo\Traits\ArrayValidator;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class for send email actions.
 *
 * @class Action_Send_Email_Abstract
 * @since 4.4.0
 */
abstract class Action_Send_Email_Abstract extends Action implements PreviewableInterface, TestableInterface {

	use ArrayValidator;

	/**
	 * Get the email type (html-template, html-raw, plain-text).
	 *
	 * @return string
	 */
	abstract public function get_email_type();

	/**
	 * Load admin props.
	 */
	public function load_admin_details() {
		$this->group = __( 'Email', 'automatewoo' );
	}

	/**
	 * Load fields.
	 */
	public function load_fields() {
		$to = new Fields\Text();
		$to->set_name( 'to' );
		$to->set_title( __( 'To', 'automatewoo' ) );
		$to->set_description( __( 'Enter emails here or use variables such as {{ customer.email }}. Multiple emails can be separated by commas. Add <b>--notracking</b> after an email to disable open and click tracking for that recipient.', 'automatewoo' ) );
		$to->set_placeholder( __( 'E.g. {{ customer.email }}, admin@example.org --notracking', 'automatewoo' ) );
		$to->set_variable_validation();
		$to->set_required();

		$subject = new Fields\Text();
		$subject->set_name( 'subject' );
		$subject->set_title( __( 'Email subject', 'automatewoo' ) );
		$subject->set_variable_validation();
		$subject->set_required();

		$reply_to = ( new EmailAddressWithName() )
			->set_name( 'reply_to' )
			->set_title( __( 'Reply to', 'automatewoo' ) )
			->set_description(
				__( 'Optionally set a reply-to email for the email. Please note that not all email delivery services support "Reply-To" email headers.', 'automatewoo' )
			);

		$this->add_field( $to );
		$this->add_field( $reply_to );
		$this->add_field( $subject );
	}

	/**
	 * Get workflow email object for this action.
	 *
	 * Sets the email subject, content and reply_to from the action options.
	 *
	 * @param string $recipient
	 * @param string $content
	 *
	 * @return Workflow_Email
	 */
	public function get_workflow_email_object( string $recipient, string $content ) {
		$subject  = $this->get_option( 'subject', true );
		$reply_to = $this->get_option( 'reply_to', true );

		$email = new Workflow_Email( $this->workflow, $recipient, $subject, $content );
		$email->set_type( $this->get_email_type() );

		if ( is_array( $reply_to ) && $reply_to[0] && is_email( $reply_to[1] ) ) {
			$email->set_reply_to( sprintf( '%s <%s>', $reply_to[0], $reply_to[1] ) );
		}

		return $email;
	}


	/**
	 * Log the result of a send email attempt.
	 *
	 * @param \WP_Error|bool $result
	 */
	public function add_send_email_result_to_workflow_log( $result ) {
		if ( is_wp_error( $result ) ) {
			$this->workflow->log_action_email_error( $result, $this );
		} else {
			$this->workflow->log_action_note( $this, __( 'Email successfully sent.', 'automatewoo' ) );
		}
	}

	/**
	 * Validate the args used to run a test of this action.
	 *
	 * @since 5.2.0
	 *
	 * @param array $args
	 *
	 * @throws InvalidArgument If test args are invalid.
	 */
	protected function validate_test_args( array $args ) {
		if ( ! isset( $args['recipients'] ) ) {
			throw InvalidArgument::missing_required( 'recipients' );
		}
		$this->validate_array_of_strings( $args['recipients'] );
	}
}
