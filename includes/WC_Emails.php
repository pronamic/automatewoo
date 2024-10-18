<?php

namespace AutomateWoo;

/**
 * Integration with WC transactional emails.
 *
 * @since 3.8
 */
class WC_Emails {

	/** @var \WC_Email */
	public static $current_email;


	/**
	 * @param string    $email_heading
	 * @param \WC_Email $email
	 */
	public static function header( $email_heading, $email ) {
		if ( $email ) {
			self::$current_email = $email;
		}
	}


	/**
	 * Unset current email property
	 */
	public static function footer() {
		self::$current_email = null;
	}


	/**
	 * @return bool
	 */
	public static function is_email() {
		return isset( self::$current_email );
	}


	/**
	 * @return \WC_Email
	 */
	public static function get_current_email_object() {
		return self::$current_email;
	}


	/**
	 * Returns the email of the current recipient
	 *
	 * @return string|false
	 */
	public static function get_current_recipient() {
		if ( self::is_email() ) {
			return self::$current_email->recipient;
		}
		return false;
	}


	/**
	 * Returns the email of the current recipient
	 *
	 * @return string|false
	 */
	public static function is_customer_email() {
		if ( self::is_email() ) {
			return self::$current_email->is_customer_email();
		}
		return false;
	}
}
