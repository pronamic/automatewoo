<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Mailer class for plain text emails.
 *
 * @since 4.4.0
 */
class Mailer_Plain_Text extends Mailer_Abstract {

	/**
	 * The email format type.
	 *
	 * @var string
	 */
	public $email_type = 'plain';


	/**
	 * Get the email body.
	 * For plain text emails simply returns the $content.
	 *
	 * @return string
	 */
	public function get_email_body() {
		return $this->content;
	}
}
