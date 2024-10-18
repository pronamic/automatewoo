<?php

namespace AutomateWoo;

/**
 * Abstract class for Mailers.
 *
 * @since 4.4.0
 */
abstract class Mailer_Abstract {

	/**
	 * The recipient of the email.
	 *
	 * @var string
	 */
	public $email;

	/**
	 * The content of the email body.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * The email subject.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * The email sender name.
	 *
	 * @var string
	 */
	public $from_name;

	/**
	 * The email sender email.
	 *
	 * @var string
	 */
	public $from_email;

	/**
	 * The email attachments.
	 *
	 * @var array
	 */
	public $attachments = [];

	/**
	 * The email reply to value e.g. 'John Smith <email@example.org>'.
	 *
	 * @var string
	 */
	public $reply_to = '';

	/**
	 * The email type.
	 *
	 * @var string (html|plain)
	 */
	public $email_type = 'html';

	/**
	 * URL to set as the List-Unsubscribe header to support one click
	 * unsubscribe. Header will not be included if value is false.
	 *
	 * @var string|bool
	 */
	public $one_click_unsubscribe = false;

	/**
	 * Returns email body, can be HTML or plain text.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	abstract public function get_email_body();


	/**
	 * Set email recipient.
	 *
	 * @param string $email
	 */
	public function set_email( $email ) {
		$this->email = $email;
	}

	/**
	 * Set the email body content.
	 *
	 * @param string $content
	 */
	public function set_content( $content ) {
		$this->content = $content;
	}

	/**
	 * Set the email subject.
	 *
	 * @param string $subject
	 */
	public function set_subject( $subject ) {
		$this->subject = $subject;
	}

	/**
	 * Set the URL for the one click unsubscribe header
	 *
	 * @param string $url The URL where a POST request will be received to unsubscribe an email address
	 *
	 * @return void
	 */
	public function set_one_click_unsubscribe( $url ) {
		$this->one_click_unsubscribe = $url;
	}

	/**
	 * Get email sender email address.
	 *
	 * @return string
	 */
	public function get_from_email() {
		if ( ! isset( $this->from_email ) ) {
			$this->from_email = Emails::get_from_address();
		}
		return $this->from_email;
	}


	/**
	 * Get email sender name.
	 *
	 * @return string
	 */
	public function get_from_name() {
		if ( ! isset( $this->from_name ) ) {
			$this->from_name = Emails::get_from_name();
		}
		return $this->from_name;
	}


	/**
	 * Validate the recipient's email address.
	 *
	 * @return true|\WP_Error
	 */
	public function validate_recipient_email() {
		if ( ! $this->email ) {
			return new \WP_Error( 'email_blank', __( 'Email address is blank.', 'automatewoo' ) );
		}

		if ( ! is_email( $this->email ) ) {
			return new \WP_Error( 'email_invalid', __( 'Email address is not valid.', 'automatewoo' ) );
		}

		if ( aw_is_email_anonymized( $this->email ) ) {
			return new \WP_Error( 'email_anonymized', __( 'Email address appears to be anonymized.', 'automatewoo' ) );
		}

		/**
		 * Filter allows blacklisting hosts or email addresses with custom code.
		 *
		 * @since 3.6.0
		 */
		$blacklist = apply_filters( 'automatewoo/mailer/blacklist', [] );

		foreach ( $blacklist as $pattern ) {
			if ( strstr( $this->email, $pattern ) ) {
				return new \WP_Error( 'email_blacklisted', __( 'Email address is blacklisted.', 'automatewoo' ) );
			}
		}

		return true;
	}


	/**
	 * Sends the email if validation passes.
	 *
	 * @return true|\WP_Error
	 */
	public function send() {

		$validate_email = $this->validate_recipient_email();

		if ( is_wp_error( $validate_email ) ) {
			return $validate_email;
		}

		do_action( 'automatewoo/email/before_send', $this );

		add_filter( 'wp_mail_from', [ $this, 'get_from_email' ] );
		add_filter( 'wp_mail_from_name', [ $this, 'get_from_name' ] );
		add_filter( 'wp_mail_content_type', [ $this, 'get_content_type' ] );
		add_action( 'wp_mail_failed', [ $this, 'log_wp_mail_errors' ] );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		$headers = [
			'Content-Type: ' . $this->get_content_type(),
		];

		if ( $this->reply_to ) {
			$headers[] = 'Reply-To: ' . $this->reply_to;
		}

		if ( $this->one_click_unsubscribe ) {
			$headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
			$headers[] = "List-Unsubscribe: <$this->one_click_unsubscribe>";
		}

		$subject = wp_specialchars_decode( $this->subject, ENT_QUOTES );
		$sent    = wp_mail(
			$this->email,
			$subject,
			$this->get_email_body(),
			$headers,
			$this->attachments
		);

		remove_filter( 'wp_mail_from', [ $this, 'get_from_email' ] );
		remove_filter( 'wp_mail_from_name', [ $this, 'get_from_name' ] );
		remove_filter( 'wp_mail_content_type', [ $this, 'get_content_type' ] );
		remove_action( 'wp_mail_failed', [ $this, 'log_wp_mail_errors' ] );
		add_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		if ( $sent === false ) {
			$phpmailer_error = $this->get_phpmailer_last_error();
			if ( $phpmailer_error ) {
				/* translators: PHP Mailer error message. */
				return new \WP_Error( 4, sprintf( __( 'PHP Mailer - %s', 'automatewoo' ), $phpmailer_error ) );
			}

			return new \WP_Error( 5, __( 'The wp_mail() function returned false.', 'automatewoo' ) );
		}

		return $sent;
	}


	/**
	 * Process email variables. Currently only {{ unsubscribe_url }}.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function process_email_variables( $content ) {
		$replacer = new Replace_Helper( $content, [ $this, 'callback_process_email_variables' ], 'variables' );
		return $replacer->process();
	}


	/**
	 * Callback function to process email variables
	 *
	 * @param string $variable
	 *
	 * @return string
	 */
	public function callback_process_email_variables( $variable ) {
		$variable = trim( $variable );
		$value    = '';

		switch ( $variable ) {
			case 'unsubscribe_url':
				$value = \AW_Mailer_API::unsubscribe_url();
				break;
		}

		return apply_filters( 'automatewoo/mailer/variable_value', $value, $this );
	}


	/**
	 * Get the email type.
	 *
	 * @return string
	 */
	public function get_email_type() {
		return $this->email_type && class_exists( 'DOMDocument' ) ? $this->email_type : 'plain';
	}


	/**
	 * Get the email content type.
	 *
	 * @return string
	 */
	public function get_content_type() {
		switch ( $this->get_email_type() ) {
			case 'html':
				return 'text/html';
			case 'multipart':
				return 'multipart/alternative';
			default:
				return 'text/plain';
		}
	}


	/**
	 * Log a WP_Error.
	 *
	 * @param \WP_Error $error
	 */
	public function log_wp_mail_errors( $error ) {
		Logger::error( 'wp-mail', $error->get_error_message() );
	}

	/**
	 * Gets the most recent PHPMailer error message.
	 *
	 * @since 5.0.3
	 *
	 * @return string
	 */
	protected function get_phpmailer_last_error() {
		global $phpmailer;

		if ( $phpmailer ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			return $phpmailer->ErrorInfo;
		}
		return '';
	}
}
