<?php

namespace AutomateWoo;

/**
 * Class PreSubmit
 *
 * @package AutomateWoo
 * @since 2.9
 */
class PreSubmit {

	/**
	 * @since 6.9.3
	 */
	const FIELD_WRITE_LIMIT = 60;

	/**
	 * @since 6.9.3
	 */
	const FIELD_WRITE_GLOBAL_LIMIT = 0;

	/**
	 * @return array
	 */
	public static function get_email_capture_selectors() {
		return apply_filters(
			'automatewoo/guest_capture_fields',
			[
				'#billing_email',
				'.automatewoo-capture-guest-email',
				'input[name="billing_email"]',
				'.wc-block-checkout #email',
			]
		);
	}


	/**
	 * @return array
	 */
	public static function get_checkout_capture_fields() {
		return apply_filters(
			'automatewoo/checkout_capture_fields',
			[
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_phone',
				'billing_country',
				'billing_address_1',
				'billing_address_2',
				'billing_city',
				'billing_state',
				'billing_postcode',
			]
		);
	}


	/**
	 * @return bool
	 */
	public static function is_capture_permitted() {
		if ( ! Options::presubmit_capture_enabled() ) {
			return false;
		}

		if ( Session_Tracker::get_detected_user_id() ) {
			return false; // don't use capture for registered users
		}

		if ( ! Session_Tracker::cookies_permitted() ) {
			return false; // sessions are disabled or blocked
		}

		return true;
	}


	/**
	 * @param string $field_name
	 * @return bool
	 */
	public static function is_checkout_capture_field( $field_name ) {
		return in_array( $field_name, self::get_checkout_capture_fields(), true );
	}


	/**
	 * Capture guest email
	 *
	 * The response is the same whatever the typed email is, so it can't be used to probe for existing customers.
	 */
	public static function ajax_capture_email() {
		self::do_capture_permitted_check();

		$email           = Clean::email( self::get_posted_string( 'email' ) );
		$language        = Clean::string( self::get_posted_string( 'language' ) );
		$checkout_fields = Clean::recursive( self::get_posted( 'checkout_fields' ) );

		$customer = Session_Tracker::set_session_by_captured_email( $email, $language );
		$guest    = $customer ? $customer->get_guest() : false;

		if ( $guest && ! Session_Tracker::is_customer_identity_capture_only( $customer ) ) {
			if ( is_array( $checkout_fields ) ) {
				foreach ( $checkout_fields as $field_name => $field_value ) {

					if ( ! self::is_checkout_capture_field( $field_name ) || empty( $field_value ) || ! is_string( $field_value ) ) {
						continue; // IMPORTANT don't save the field if it is empty
					}

					if ( self::is_field_write_over_limit() ) {
						break;
					}

					$guest->update_meta( $field_name, mb_substr( $field_value, 0, 255 ) );
				}
			} else {
				$location = wc_get_customer_default_location();
				if ( $location['country'] ) {
					$guest->update_meta( 'billing_country', $location['country'] );
				}
			}
		}

		Ajax::send_json_success();
	}


	/**
	 * Capture an additional field from the checkout page
	 */
	public static function ajax_capture_checkout_field() {
		self::do_capture_permitted_check();

		$field_name  = Clean::string( self::get_posted_string( 'field_name' ) );
		$field_value = mb_substr( Clean::string( self::get_posted_string( 'field_value' ) ), 0, 255 );

		$guest = Session_Tracker::get_current_guest();

		if ( $guest && self::is_checkout_capture_field( $field_name ) && ! Session_Tracker::is_customer_identity_capture_only( Session_Tracker::get_session_customer() ) && ! self::is_field_write_over_limit() ) {
			$guest->update_meta( $field_name, $field_value );
		}

		Ajax::send_json_success();
	}


	/**
	 * @since 6.9.3
	 *
	 * @return bool
	 */
	private static function is_field_write_over_limit() {
		/**
		 * Filters how many pre-submit field writes one IP address can make in 15 minutes.
		 *
		 * @since 6.9.3
		 *
		 * @param int $limit Default 60. Use 0 to disable the limit.
		 */
		$limit = (int) apply_filters( 'automatewoo/presubmit/field_write_limit', self::FIELD_WRITE_LIMIT );

		/**
		 * Filters how many pre-submit field writes all visitors together can make in 15 minutes.
		 *
		 * @since 6.9.3
		 *
		 * @param int $limit Default 0, which is off. Set a number to turn the limit on.
		 */
		$global_limit = (int) apply_filters( 'automatewoo/presubmit/field_write_global_limit', self::FIELD_WRITE_GLOBAL_LIMIT );

		return Rate_Limiter::is_over_limit( 'presubmit_field', $limit, 15 * MINUTE_IN_SECONDS, $global_limit, 'presubmit' );
	}


	/**
	 * @since 6.9.3
	 *
	 * @param string $key
	 *
	 * @return string Empty string when the value is missing or not a scalar.
	 */
	private static function get_posted_string( $key ) {
		$value = self::get_posted( $key );

		return is_scalar( $value ) ? (string) $value : '';
	}


	/**
	 * Reads a value from the POST body only. Callers sanitise it by type.
	 *
	 * @since 6.9.3
	 *
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	private static function get_posted( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Requests are checked in do_capture_permitted_check(); callers sanitise by type.
		return isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : null;
	}


	/**
	 * @return array
	 */
	public static function get_js_params() {
		$params = [];

		$params['guest_id']                   = 0; // Kept for backward compatibility.
		$params['language']                   = Language::get_current();
		$params['email_capture_selectors']    = self::get_email_capture_selectors();
		$params['checkout_capture_selectors'] = self::get_checkout_capture_fields();
		$params['ajax_url']                   = Ajax::get_endpoint( '%%endpoint%%' );

		return $params;
	}


	/**
	 * Checks that a pre-submit request is from the store's own pages and that capture is permitted.
	 * On failure a bare error JSON is sent and the request dies.
	 *
	 * The request check is a same-site POST.
	 *
	 * @since 4.4.0
	 */
	public static function do_capture_permitted_check() {
		if (
			! Request_Origin::is_post()
			|| ! Request_Origin::is_same_site()
			|| ! self::is_capture_permitted()
		) {
			Ajax::send_json_error();
		}

		// Runs for every accepted request, before the email is looked at.
		Session_Tracker::maybe_start_wc_session_cookie();
	}
}
