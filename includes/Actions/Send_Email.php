<?php

namespace AutomateWoo;

use AutomateWoo\Exceptions\Exception;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * @class Action_Send_Email
 */
class Action_Send_Email extends Action_Send_Email_Abstract {

	/**
	 * Get the email type.
	 *
	 * @return string
	 */
	public function get_email_type() {
		return 'html-template';
	}

	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		parent::load_admin_details();

		$this->title       = __( 'Send Email', 'automatewoo' );
		$this->description = sprintf(
			// translators: %1$s anchor tag with the link to the documentation link, %2$s closing tag for the anchor
			__( 'This action sends an HTML email using a template. The default template matches the style of your WooCommerce transactional emails. <%1$s>View email templates documentation<%2$s>.', 'automatewoo' ),
			'a href="' . Admin::get_docs_link( 'email/templates', 'action-description' ) . '" target="_blank"',
			'/a'
		);
	}

	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {
		parent::load_fields();

		$heading = ( new Fields\Text() )
			->set_name( 'email_heading' )
			->set_title( __( 'Email heading', 'automatewoo' ) )
			->set_variable_validation()
			->set_description( __( 'The appearance will depend on your email template. Not all templates support this field.', 'automatewoo' ) );

		$preheader = ( new Fields\Text() )
			->set_name( 'preheader' )
			->set_title( __( 'Email preheader', 'automatewoo' ) )
			->set_variable_validation()
			->set_description( __( 'A preheader is a short text summary that follows the subject line when an email is viewed in the inbox. If no preheader is set the first text found in the email is used.', 'automatewoo' ) );

		$template = ( new Fields\Select( false ) )
			->set_name( 'template' )
			->set_title( __( 'Template', 'automatewoo' ) )
			->set_description( __( 'Select which template to use when formatting the email. If you select \'None\', the email will have no template but the email will still be sent as an HTML email.', 'automatewoo' ) )
			->set_options( Emails::get_email_templates() );

		$email_content = ( new Fields\Email_Content() ); // no easy way to define data attributes

		$this->add_field( $heading );
		$this->add_field( $preheader );
		$this->add_field( $template );
		$this->add_field( $email_content );
	}


	/**
	 * Generates the HTML content for the email
	 *
	 * @return string|\WP_Error
	 */
	public function get_preview() {
		$current_user = wp_get_current_user();

		// no user should be logged in
		// When the user_id value is 0, it's a session for a logged-out user
		// see https://wordpress.org/support/topic/sessions-with-user-id-0/
		// phpcs:ignore
		wp_set_current_user( 0 );

		return $this->get_workflow_email_object(
			$current_user->get( 'user_email' ),
			$this->get_option( 'email_content', true, true )
		)
			->set_heading( $this->get_option( 'email_heading', true ) )
			->set_preheader( trim( $this->get_option( 'preheader', true ) ) )
			->set_template( $this->get_option( 'template' ) )
			->get_email_body();
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

			$heading   = $this->get_option( 'email_heading', true );
			$content   = $this->get_option( 'email_content', true, true );
			$preheader = trim( $this->get_option( 'preheader', true ) );
			$template  = $this->get_option( 'template' );

			foreach ( $args['recipients'] as $recipient ) {
				$sent = $this->get_workflow_email_object( $recipient, $content )
					->set_heading( $heading )
					->set_preheader( $preheader )
					->set_template( $template )
					->send();

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
	 * Run the action
	 */
	public function run() {
		$recipients = $this->get_option( 'to', true );
		$heading    = $this->get_option( 'email_heading', true );
		$content    = $this->get_option( 'email_content', true, true );
		$preheader  = $this->get_option( 'preheader', true );
		$template   = $this->get_option( 'template' );

		$recipients = Emails::parse_recipients_string( $recipients );

		foreach ( $recipients as $recipient_email => $recipient_args ) {
			$email = $this->get_workflow_email_object( $recipient_email, $content )
				->set_heading( $heading )
				->set_preheader( $preheader )
				->set_template( $template );

			if ( $recipient_args['notracking'] ) {
				$email->set_tracking_enabled( false );
			}

			$sent = $email->send();

			$this->add_send_email_result_to_workflow_log( $sent );
		}
	}
}
