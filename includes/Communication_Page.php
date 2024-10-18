<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Communication_Page
 *
 * @since 4.0
 */
class Communication_Page {

	/** @var string $using_customer_key True if the customer is retreived using a customer key */
	private static $using_customer_key = false;

	/**
	 * Init runs when on the communication preferences page
	 */
	public static function init() {
		aw_no_page_cache();
	}

	/**
	 * Get the contents of the communication preferences shortcode
	 *
	 * @return string
	 */
	public static function output_preferences_shortcode() {
		$customer     = false;
		$customer_key = Clean::string( aw_request( 'customer_key' ) );

		if ( $customer_key ) {
			self::$using_customer_key = true;
			$customer                 = Customer_Factory::get_by_key( $customer_key );
		} elseif ( is_user_logged_in() ) {
			$customer = Customer_Factory::get_by_user_id( get_current_user_id() );
		}

		ob_start();
		self::output_preferences_form( $customer );
		return ob_get_clean();
	}

	/**
	 * Output the communication preferences page
	 *
	 * @param Customer $customer
	 */
	public static function output_preferences_form( $customer ) {
		$data = [];

		wp_enqueue_style( 'automatewoo-communication-page' );

		$data['intent'] = Clean::string( aw_request( 'intent' ) );

		if ( ! $customer ) {
			aw_get_template( 'communication-preferences/communication-form-no-customer.php', $data );
		} else {
			$data['customer'] = $customer;

			if ( self::$using_customer_key && 'unsubscribe' === $data['intent'] && ! aw_request( 'automatewoo_save_changes' ) ) {
				$customer->opt_out();
				wc_add_notice( __( "Saved successfully! You won't receive marketing communications from us.", 'automatewoo' ) );
			}

			aw_get_template( 'communication-preferences/communication-form.php', $data );
		}
	}

	/**
	 * Get the contents of the communication preferences signup form
	 *
	 * @return string
	 */
	public static function output_signup_form() {
		wp_enqueue_style( 'automatewoo-communication-page' );
		ob_start();
		aw_get_template( 'communication-preferences/signup-form.php' );
		return ob_get_clean();
	}
}
