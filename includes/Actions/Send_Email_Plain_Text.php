<?php

namespace AutomateWoo;

use AutomateWoo\Exceptions\Exception;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Action_Send_Email_Plain_Text class.
 *
 * @since 4.4.0
 */
class Action_Send_Email_Plain_Text extends Action_Send_Email_Abstract {

	/**
	 * Get the email type.
	 *
	 * @return string
	 */
	public function get_email_type() {
		return 'plain-text';
	}

	/**
	 * Load admin props.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Send Email - Plain Text', 'automatewoo' );
		$this->description = __( 'This action sends a plain text email. It will contain no HTML which means open tracking and click tracking will not work. Some variables may display unexpectedly due to having HTML removed. If necessary, an unsubscribe link will be added after the email content.', 'automatewoo' );
	}

	/**
	 * Load action fields.
	 */
	public function load_fields() {
		parent::load_fields();

		$text = new Fields\Text_Area();
		$text->set_name( 'email_content' );
		$text->set_title( __( 'Email content', 'automatewoo' ) );
		$text->set_description( __( 'All HTML will be removed from this field when sending. Variables that use HTML may display unexpectedly because of this.', 'automatewoo' ) );
		$text->set_variable_validation();
		$text->set_rows( 14 );

		$this->add_field( $text );
	}

	/**
	 * Generates the HTML content for the email.
	 *
	 * @return string|\WP_Error
	 */
	public function get_preview() {
		$content      = $this->get_plain_text_email_content_option();
		$current_user = wp_get_current_user();

		// When the user_id value is 0, it's a session for a logged-out user
		// see https://wordpress.org/support/topic/sessions-with-user-id-0/
		// phpcs:ignore
		wp_set_current_user( 0 ); // no user should be logged in

		$email_body = $this->get_workflow_email_object( $current_user->get( 'user_email' ), $content )
			->get_email_body();

		// convert new lines to HTML breaks for preview only
		return nl2br( $email_body, false );
	}

	/**
	 * Run the action as a test.
	 *
	 * @param array $args Optionally add args for the test.
	 *
	 * @return true|WP_Error
	 */
	public function run_test( array $args = [] ) {
		try {
			$this->validate_test_args( $args );

			$content = $this->get_plain_text_email_content_option();

			foreach ( $args['recipients'] as $recipient ) {
				$sent = $this->get_workflow_email_object( $recipient, $content )->send();

				if ( is_wp_error( $sent ) ) {
					return $sent;
				}
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'exception', $e->getMessage() );
		}

		return true;
	}

	/**
	 * Run the action.
	 */
	public function run() {
		$content    = $this->get_plain_text_email_content_option();
		$recipients = $this->get_option( 'to', true );
		$cc         = $this->get_option( 'cc', true );
		$bcc        = $this->get_option( 'bcc', true );

		$recipients     = Emails::parse_recipients_string( $recipients );
		$cc_recipients  = Emails::parse_recipients_string( $cc );
		$bcc_recipients = Emails::parse_recipients_string( $bcc );

		foreach ( $recipients as $recipient_email => $recipient_args ) {
			$email = $this->get_workflow_email_object( $recipient_email, $content );

			if ( ! empty( $recipient_args['notracking'] ) ) {
				$email->set_tracking_enabled( false );
			}

			// Add CC recipients
			if ( ! empty( $cc_recipients ) ) {
				$email->set_cc( array_keys( $cc_recipients ) );
			}

			// Add BCC recipients
			if ( ! empty( $bcc_recipients ) ) {
				$email->set_bcc( array_keys( $bcc_recipients ) );
			}

			$sent = $email->send();
			$this->add_send_email_result_to_workflow_log( $sent );
		}
	}

	/**
	 * Get the email content with plain-text-specific variable formatting enabled.
	 *
	 * @return string
	 */
	protected function get_plain_text_email_content_option() {
		add_filter( 'automatewoo/variables/after_get_value', [ $this, 'format_order_items_variable_for_plain_text' ], 10, 5 );

		try {
			return $this->get_option( 'email_content', true );
		} finally {
			remove_filter( 'automatewoo/variables/after_get_value', [ $this, 'format_order_items_variable_for_plain_text' ], 10 );
		}
	}

	/**
	 * Format order items for plain-text emails.
	 *
	 * @param string   $value      Variable value.
	 * @param string   $data_type  Variable data type.
	 * @param string   $data_field Variable data field.
	 * @param array    $parameters Variable parameters.
	 * @param Workflow $workflow   Workflow object.
	 * @return string
	 */
	public function format_order_items_variable_for_plain_text( $value, $data_type, $data_field, $parameters, $workflow ) {
		if ( 'order' !== $data_type || 'items' !== $data_field ) {
			return $value;
		}

		return $this->format_html_variable_for_plain_text( $value );
	}

	/**
	 * Convert HTML variable output to compact plain text.
	 *
	 * HTML entities are intentionally left encoded here. The processed email content is
	 * passed through wp_strip_all_tags() and html_entity_decode() once more by
	 * Variables_Processor::process_field(), so entity decoding is deferred to that single,
	 * final pass to avoid double-decoding. Note that a product name containing a literal
	 * angle-bracket substring (e.g. "Size <XL>") is escaped to an entity by the template
	 * and then dropped by the final tag strip - this is intended: arbitrary product names
	 * must not be able to inject markup into the plain-text body.
	 *
	 * @param string $html HTML content.
	 * @return string
	 */
	protected function format_html_variable_for_plain_text( $html ) {
		$html = str_replace( [ "\r\n", "\r" ], "\n", $html );
		$html = preg_replace( '#<(script|style)[^>]*?>.*?</\\1>#si', '', $html );
		$html = preg_replace( '#<(br|/p|/div|/h[1-6]|/li|/tr|/td|/th|/table)[^>]*>#i', "\n", $html );

		$text = wp_strip_all_tags( $html );
		$text = preg_replace( "/[ \t]+/", ' ', $text );
		$text = preg_replace( "/ *\n+ */", "\n", $text );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );

		return trim( $text );
	}
}
