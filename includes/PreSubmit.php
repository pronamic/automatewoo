<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * Class PreSubmit
 * @package AutomateWoo
 * @since 2.9
 */
class PreSubmit {

	/**
	 * @return array
	 */
	static function get_email_capture_selectors() {
		return apply_filters( 'automatewoo/guest_capture_fields', [
			'.woocommerce-checkout [type="email"]',
			'#billing_email',
			'.automatewoo-capture-guest-email',
			'input[name="billing_email"]',
		]);
	}


	/**
	 * @return array
	 */
	static function get_checkout_capture_fields() {
		return apply_filters( 'automatewoo/checkout_capture_fields', [
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_phone',
			'billing_country',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode'
		]);
	}


	/**
	 * @return bool
	 */
	static function is_capture_permitted() {
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
	 * @param $field_name
	 * @return bool
	 */
	static function is_checkout_capture_field( $field_name ) {
		return in_array( $field_name, self::get_checkout_capture_fields() );
	}


	/**
	 * Capture guest email
	 */
	static function ajax_capture_email() {
		self::do_capture_permitted_check();

		$email = Clean::email( aw_request('email') );
		$language = Clean::string( aw_request( 'language' ) );
		$checkout_fields = Clean::recursive( aw_request( 'checkout_fields' ) );
		$maybe_guest_or_user = Customer_Factory::get_by_email( $email, false );

		if ( $maybe_guest_or_user ) {
			Ajax::send_json_error();
		}

		$customer = Session_Tracker::set_session_by_captured_email( $email, $language );

		if ( ! $customer ) {
			Ajax::send_json_error();
		}

		if ( ! $guest = $customer->get_guest() ) {
			Ajax::send_json_error();
		}

		if ( is_array( $checkout_fields ) )  {
            foreach ( $checkout_fields as $field_name => $field_value ) {

                if ( ! self::is_checkout_capture_field( $field_name ) || empty( $field_value ) ) {
                  continue; // IMPORTANT don't save the field if it is empty
                }

                $guest->update_meta( $field_name, stripslashes( $field_value ) );
            }
        }
        else {
            $location = wc_get_customer_default_location();
            if ( $location['country'] ) {
                $guest->update_meta( 'billing_country', $location['country'] );
            }
        }

        Ajax::send_json_success([
           'guest_id' => $guest->get_id()
        ]);
	}


	/**
	 * Capture an additional field from the checkout page
	 */
	static function ajax_capture_checkout_field() {
		self::do_capture_permitted_check();

		$guest_id = absint( aw_request( 'guest_id' ) );
		$field_name = Clean::string( aw_request( 'field_name' ) );
		$field_value = stripslashes( Clean::string( aw_request( 'field_value' ) ) );

		$guest = Session_Tracker::get_current_guest();

		if ( ! $guest || $guest_id != $guest->get_id() ) {
			Ajax::send_json_error();
		}

		if ( self::is_checkout_capture_field( $field_name ) ) {
			$guest->update_meta( $field_name, $field_value );
		}

		Ajax::send_json_success();
	}


	/**
	 * @return array
	 */
	static function get_js_params() {
		$params = [];

		$guest = Session_Tracker::get_current_guest();

		$params['guest_id'] = $guest ? $guest->get_id() : 0;
		$params['language'] = Language::get_current();
		$params['email_capture_selectors'] = self::get_email_capture_selectors();
		$params['checkout_capture_selectors'] = self::get_checkout_capture_fields();
		$params['ajax_url'] = Ajax::get_endpoint( '%%endpoint%%' );

		return $params;
	}


	/**
	 * Checks if pre-submit capture is permitted.
	 * If capture, not permitted, error JSON will be sent and request will die.
	 *
	 * @since 4.4.0
	 */
	static function do_capture_permitted_check() {
		if ( ! self::is_capture_permitted() ) {
			Ajax::send_json_error( [
				'presubmit_capture_enabled' => Options::presubmit_capture_enabled(),
				'session_tracking_enabled'  => Options::session_tracking_enabled(),
				'session_cookies_permitted' => Session_Tracker::cookies_permitted(),
			] );
		}
	}


}
