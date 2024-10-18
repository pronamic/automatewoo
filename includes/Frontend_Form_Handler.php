<?php
// phpcs:ignoreFile

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Frontend_Form_Handler
 * @since 3.9
 */
class Frontend_Form_Handler {

	/** @var string */
	public static $current_action = '';


	private static $actions = [
		'automatewoo_save_communication_preferences',
		'automatewoo_save_communication_signup',
	];



	/**
	 * Handle frontend form post
	 */
	static function handle() {
		$action              = Clean::string( $_POST['action'] );
		$honeypot_field_name = apply_filters( 'automatewoo/honeypot_field/name', 'firstname' );

		if ( ! in_array( $action, self::$actions ) || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $action ) ) {
			return;
		}

		if ( ! empty( $_POST[ $honeypot_field_name ] ) ) {
			wc_add_notice( sprintf( __( 'The form could not be submitted. Error code: %s', 'automatewoo' ), 1 ), 'error' );
			return;
		}

		$action = str_replace( 'automatewoo_', '', $action );
		self::$current_action = $action;

		nocache_headers();

		call_user_func( [ __CLASS__, $action ] );
	}



	static function save_communication_preferences() {
		$customer = isset( $_POST['customer_key'] ) ? Customer_Factory::get_by_key( $_POST['customer_key'] ): false;

		if ( ! $customer ) {
			return;
		}

		self::update_customer_preferences( $customer );

		wc_add_notice( __( 'Your communication preferences were updated.', 'automatewoo' ) );
	}



	static function save_communication_signup() {

		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

		$maybe_customer = Customer_Factory::get_by_email( $email, false );

		if ( $maybe_customer ) {
			wc_add_notice( __( 'It was not possible to update communication preferences for this email.', 'automatewoo' ), 'error' );
			return;
		}

		$customer = Customer_Factory::get_by_email( $email );

		if ( ! $customer ) {
			wc_add_notice( __( 'Please enter a valid email address.', 'automatewoo' ), 'error' );
			return;
		}

		self::update_customer_preferences( $customer );

		if ( $customer->is_opted_in() ) {
			wc_add_notice( __( 'Thanks! Your signup was successful.', 'automatewoo' ) );
		}
		else {
			wc_add_notice( __( "Saved successfully! You won't receive marketing communications from us.", 'automatewoo' ) );
		}

	}


	/**
	 * @param Customer $customer
	 */
	protected static function update_customer_preferences( $customer ) {
		if ( isset( $_POST['subscribe'] ) ) {
			$customer->opt_in();
		}
		else {
			$customer->opt_out();
		}

		// try and start session tracking the customer
		Session_Tracker::set_session_customer( $customer );

		do_action( 'automatewoo/communication_page/save_preferences', $customer );

	}

}
