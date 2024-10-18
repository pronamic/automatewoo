<?php

namespace AutomateWoo;

/**
 * Tracks logged out customers via cookies.
 *
 * @class Session_Tracker
 *
 * @since 4.3.0 Class was essentially rewritten.
 */
class Session_Tracker {

	/** @var int (days) */
	private static $tracking_cookie_expiry;

	/** @var string cookie name */
	private static $tracking_key_cookie_name;

	/** @var string - This key WILL BE saved */
	private static $tracking_key_to_set = '';


	/**
	 * Init session tracker, add hooks.
	 */
	public static function init() {
		$self = 'AutomateWoo\Session_Tracker'; /** @var $self Session_Tracker */

		if ( ! Options::session_tracking_enabled() ) {
			return;
		}

		self::$tracking_key_cookie_name = apply_filters( 'automatewoo/session_tracker/cookie_name', 'wp_automatewoo_visitor_' . COOKIEHASH );
		self::$tracking_cookie_expiry   = apply_filters( 'automatewoo_visitor_tracking_cookie_expiry', 365 ); // in days

		add_action( 'wp', [ $self, 'maybe_set_session_cookies' ], 99 );
		add_action( 'shutdown', [ $self, 'maybe_set_session_cookies' ], 0 );
		add_action( 'automatewoo/ajax/before_send_json', [ $self, 'maybe_set_session_cookies' ] );

		add_action( 'set_logged_in_cookie', [ $self, 'update_session_on_user_login' ], 10, 4 );

		add_action( 'comment_post', [ $self, 'capture_from_comment' ], 10, 2 );
		add_action( 'woocommerce_before_order_object_save', [ $self, 'capture_from_store_api' ], 10, 1 );
		add_action( 'automatewoo_capture_guest_email', [ $self, 'set_session_by_captured_email' ] ); // for third-party
		add_action( 'woocommerce_checkout_order_processed', [ $self, 'maybe_track_guest_customer_after_order_placed' ], 20 );

		// Checkout blocks uses woocommerce_store_api_checkout_order_processed instead of  woocommerce_checkout_order_processed
		add_action( 'woocommerce_store_api_checkout_order_processed', [ $self, 'maybe_track_guest_customer_after_order_placed' ] );
	}


	/**
	 * Returns true if a session tracking cookie has been set.
	 *
	 * Note: Includes any changes to the cookie in the current request.
	 *
	 * @since 4.0
	 *
	 * @return bool
	 */
	public static function is_tracking_cookie_set() {
		return (bool) Cookies::get( self::$tracking_key_cookie_name );
	}


	/**
	 * Returns true if a session tracking cookie has been set.
	 *
	 * Note: Includes any changes to the cookie in the current request.
	 *
	 * @since 4.2
	 *
	 * @return bool
	 */
	public static function is_session_started_cookie_set() {
		return (bool) Cookies::get( 'wp_automatewoo_session_started' );
	}


	/**
	 * Returns the tracking key as currently stored in the cookie.
	 *
	 * @since 4.3
	 *
	 * @return string
	 */
	public static function get_tracking_cookie() {
		return Clean::string( Cookies::get( self::$tracking_key_cookie_name ) );
	}


	/**
	 * This method doesn't actually set the cookie, rather it initiates the cookie setting.
	 * Cookies are set only on 'wp', 'shutdown' or 'automatewoo/ajax/before_send_json'.
	 *
	 * @since 4.3
	 *
	 * @param string $tracking_key
	 *
	 * @return bool
	 */
	public static function set_tracking_key_to_be_set( $tracking_key ) {
		if ( headers_sent() ) {
			return false; // cookies can't be set
		}

		self::$tracking_key_to_set = $tracking_key;
		return true;
	}


	/**
	 * If session cookies aren't permitted session tracking is basically disabled.
	 *
	 * @since 4.0
	 * @return bool
	 */
	public static function cookies_permitted() {
		if ( ! Options::session_tracking_enabled() ) {
			return false;
		}

		$permitted = true;

		if ( Options::session_tracking_requires_cookie_consent() ) {
			$permitted = false;

			$consent_cookie = Options::session_tracking_consent_cookie_name();

			// if consent cookie name is set and that cookie exists then permit cookies
			if ( $consent_cookie && Cookies::get( $consent_cookie ) ) {
				$permitted = true;
			}
		}

		return apply_filters( 'automatewoo/session_tracking/cookies_permitted', $permitted );
	}


	/**
	 * Clear session cookies
	 *
	 * @since 4.3
	 */
	public static function clear_tracking_cookies() {
		self::$tracking_key_to_set = '';
		Cookies::clear( self::$tracking_key_cookie_name );
		Cookies::clear( 'wp_automatewoo_session_started' );
	}


	/**
	 * New browser session initiated
	 */
	public static function new_session_initiated() {
		$guest = self::get_current_guest();
		if ( $guest ) {
			$guest->do_check_in();
		}
		do_action( 'automatewoo_new_session_initiated' );
	}


	/**
	 * Sets a new session cookie for the logged in customer.
	 * Clears any stored guest cart before their cookie key is updated.
	 *
	 * @param string $logged_in_cookie The logged-in cookie value.
	 * @param int    $expire           The time the login grace period expires as a UNIX timestamp.
	 *                                 Default is 12 hours past the cookie's expiration time.
	 * @param int    $expiration       The time when the logged-in authentication cookie expires as a UNIX timestamp.
	 *                                 Default is 14 days from now.
	 * @param int    $user_id          User ID.
	 */
	public static function update_session_on_user_login( $logged_in_cookie, $expire, $expiration, $user_id ) {
		$new_customer = Customer_Factory::get_by_user_id( $user_id );

		if ( ! $new_customer ) {
			return; // $user_id is not always set, as in #48
		}

		self::maybe_clear_previous_session_customers_cart( $new_customer );
		self::set_session_customer( $new_customer );
	}


	/**
	 * Attempt to set session tracking cookies.
	 *
	 * Doesn't set cookies in the admin area.
	 */
	public static function maybe_set_session_cookies() {
		if ( headers_sent() || is_admin() ) {
			return;
		}

		// if cookies are not permitted clear cookies and bail
		if ( ! self::cookies_permitted() ) {
			self::clear_tracking_cookies();
			return;
		}

		/**
		 * Tracking cookie needs updating when:
		 * - it's a new session
		 * - the cookie doesn't match the logged in user
		 */
		$cookie_needs_updating = false;
		$logged_in_customer    = aw_get_logged_in_customer();

		if ( ! self::is_session_started_cookie_set() ) {
			$cookie_needs_updating = true;
		}

		if ( $logged_in_customer ) {
			self::maybe_clear_previous_session_customers_cart( $logged_in_customer );

			if ( $logged_in_customer->get_key() !== self::get_tracking_cookie() ) {
				$cookie_needs_updating = true;
			}
		}

		if ( $cookie_needs_updating && $logged_in_customer ) {
			self::$tracking_key_to_set = $logged_in_customer->get_key();
		}

		// Set the tracking cookie if one needs setting
		if ( self::$tracking_key_to_set ) {
			Cookies::set( self::$tracking_key_cookie_name, self::$tracking_key_to_set, time() + DAY_IN_SECONDS * self::$tracking_cookie_expiry );
			self::$tracking_key_to_set = false; // Don't need to set the tracking cookie again
		}

		// If a tracking cookie is set but no session started cookie, init the session now.
		// MUST not set the session cookie until we have a tracking key.
		if ( self::is_tracking_cookie_set() && ! self::is_session_started_cookie_set() ) {
			// check the tracking is valid before starting a session
			if ( Customer_Factory::get_by_key( self::get_tracking_cookie() ) ) {
				Cookies::set( 'wp_automatewoo_session_started', 1 );
				self::new_session_initiated();
			} else {
				// invalid or legacy session key so clear the cookie
				self::clear_tracking_cookies();
			}
		}
	}


	/**
	 * To avoid duplicate carts this method can be used to clear the cart when switching session customers.
	 *
	 * $new_customer is the customer that will be set.
	 * The current session customer is retrieved from the current cookie value.
	 *
	 * @param Customer $new_customer
	 *
	 * @since 4.3.0
	 */
	public static function maybe_clear_previous_session_customers_cart( $new_customer ) {
		$tracking_cookie = self::get_tracking_cookie();
		if ( ! $tracking_cookie ) {
			return;
		}

		if ( $new_customer->get_key() === $tracking_cookie ) {
			return; // don't clear if the new key is the same as the current one
		}

		$tracked_customer = Customer_Factory::get_by_key( $tracking_cookie );
		if ( $tracked_customer ) {
			$cart = $tracked_customer->get_cart();
			if ( $cart ) {
				$cart->delete();
			}
		}
	}


	/**
	 * @return string|false
	 */
	public static function get_current_tracking_key() {
		if ( ! Options::session_tracking_enabled() ) {
			return false;
		}

		// If a new tracking key will be set in the request, use that in favour of current cookie value
		if ( self::$tracking_key_to_set && ! headers_sent() ) {
			return self::$tracking_key_to_set;
		}

		return self::get_tracking_cookie();
	}


	/**
	 * Returns the current user ID factoring in any session cookies.
	 *
	 * @return int
	 */
	public static function get_detected_user_id() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		}

		if ( ! Options::session_tracking_enabled() ) {
			return 0; // only return the real user id
		}

		$customer = self::get_session_customer();

		if ( $customer && $customer->is_registered() ) {
			return $customer->get_user_id();
		}

		return 0;
	}


	/**
	 * Returns the current guest from tracking cookie.
	 *
	 * @return Guest|bool
	 */
	public static function get_current_guest() {
		if ( ! Options::session_tracking_enabled() ) {
			return false;
		}

		if ( is_user_logged_in() ) {
			return false;
		}

		$customer = self::get_session_customer();

		if ( $customer && ! $customer->is_registered() ) {
			return $customer->get_guest();
		}

		return false;
	}


	/**
	 * Updates the current session based on the customer's email.
	 *
	 * Create the customer for the email if needed and contains logic to handle when a customers email changes.
	 *
	 * Cases to handle:
	 *
	 * - Registered user is logged in or remembered via cookie = bail
	 * - Email matches existing customer
	 *      - Cookie customer exists
	 *          - Cookie and matched customer are the same = do nothing
	 *          - Cookie and matched customer are different = cookie must be changed, clear cart from previous key to avoid duplicates
	 *      - No cookie customer = Set new cookie to matched customer key
	 * - Email is new
	 *      - Cookie customer exists
	 *          - Customer data is locked = create new customer, change cookie, clear cart from previous key to avoid duplicates
	 *          - Customer data is not locked = update customer email
	 *      - No cookie customer = Set new cookie to matched customer key
	 *
	 * @param string $new_email
	 * @param string $language
	 *
	 * @return Customer|false
	 */
	public static function set_session_by_captured_email( $new_email, $language = '' ) {
		if ( ! is_email( $new_email ) || headers_sent() || ! Options::session_tracking_enabled() ) {
			// must have a valid email, be able to set cookies, have session tracking enabled
			return false;
		}

		$new_email                 = Clean::email( $new_email );
		$existing_session_customer = self::get_session_customer(); // existing session customer from cookie
		$customer_matching_email   = Customer_Factory::get_by_email( $new_email, false ); // important! don't create new customer
		$email_is_new              = $customer_matching_email === false;

		if ( $existing_session_customer && $existing_session_customer->is_registered() ) {
			return $existing_session_customer; // bail if a registered user is already being tracked
		}

		// Check if a customer already exists matching the supplied email
		if ( $customer_matching_email ) {

			if ( ! $existing_session_customer || $new_email !== $existing_session_customer->get_email() ) {
				// Customer has changed so delete the cart for the existing customer
				// To avoid duplicate abandoned cart emails
				if ( $existing_session_customer ) {
					$existing_session_customer->delete_cart();
				}
			}

			// Set the matched customer as the new customer
			$new_customer = $customer_matching_email;
		} elseif ( $existing_session_customer ) { // Is there an existing session customer
			// Check if existing and new emails are the same
			// This is actually impossible considering the previous logic but it's probably more confusing to omit this
			if ( $existing_session_customer->get_email() === $new_email ) {
				// Nothing to do
				$new_customer = $existing_session_customer;
			} else {
				$guest = $existing_session_customer->get_guest(); // customer can not be a registered user at this point

				if ( $guest->is_locked() ) {
					// email has changed and guest is locked so we must create a new guest
					// first clear the old guests cart, to avoid duplicate abandoned cart emails
					$guest->delete_cart();
					$new_customer = Customer_Factory::get_by_email( $new_email );
				} else {
					// Guest is not locked so we can simply update guest email
					$guest->set_email( $new_email );
					$guest->save();

					// Set the new customer to the existing session customer
					$new_customer = $existing_session_customer;
				}
			}
		} else {
			// There is no session customer, so create one
			$new_customer = Customer_Factory::get_by_email( $new_email );
		}

		// init the new customer tracking, also saves/updates the language
		if ( $new_customer ) {
			self::set_session_customer( $new_customer, $language );
		}

		// the new customer could be a user, if the email address matched a user
		$guest = $new_customer->get_guest();
		if ( $guest ) {
			$guest->do_check_in();
		}

		// update the stored cart
		if ( Options::abandoned_cart_enabled() ) {
			Carts::update_stored_customer_cart( $new_customer );
		}

		if ( $email_is_new && $guest ) {
			// fire hook after new email is stored
			do_action( 'automatewoo/session_tracker/new_stored_guest', $guest );
		}

		return $new_customer;
	}


	/**
	 * Store guest info if they place a comment
	 *
	 * @param int $comment_id
	 */
	public static function capture_from_comment( $comment_id ) {
		if ( is_user_logged_in() ) {
			return;
		}

		$comment = get_comment( $comment_id );

		if ( $comment && ! $comment->user_id ) {
			self::set_session_by_captured_email( $comment->comment_author_email );
		}
	}

	/**
	 * Store guest info when they submit email from Store API.
	 *
	 * The guest email, first name and last name are captured.
	 *
	 * @see \Automattic\WooCommerce\Blocks\StoreApi\Routes\CartUpdateCustomer
	 *
	 * @param \WC_Order $order
	 */
	public static function capture_from_store_api( $order ) {
		if ( ! Options::presubmit_capture_enabled() || is_user_logged_in() ) {
			return;
		}

		if ( $order->get_status() !== 'checkout-draft' || ! $order->is_created_via( 'store-api' ) || ! $order->get_billing_email() || $order->get_customer_id() ) {
			return;
		}

		$customer = self::set_session_by_captured_email( $order->get_billing_email() );

		if ( $customer ) {
			// Capture the guest's data
			$guest = $customer->get_guest();
			if ( $guest ) {
				foreach ( PreSubmit::get_checkout_capture_fields() as $field ) {
					$value = $guest->get_checkout_field_from_order( $field, $order );
					if ( $value !== false && $value !== '' ) {
						$field = Clean::string( $field );
						$value = stripslashes( Clean::string( $value ) );
						$guest->update_meta( $field, $value );
					}
				}
			}
		}
	}


	/**
	 * Attempt to set a tracking key for guests when they place an order.
	 * Otherwise, if presubmit tracking is disabled, guests won't have session tracking.
	 *
	 * @since 4.0
	 * @param int $order_id
	 */
	public static function maybe_track_guest_customer_after_order_placed( $order_id ) {
		if ( ! self::cookies_permitted() ) {
			return; // cookies blocked
		}

		$order = wc_get_order( $order_id );

		if ( ! $order || is_user_logged_in() ) {
			return;
		}

		$customer = Customer_Factory::get_by_order( $order );
		if ( ! $customer ) {
			return;
		}

		self::set_session_customer( $customer );
	}


	/**
	 * Attempts to set the $customer as the current session customer.
	 * Should only be used before headers are sent.
	 * Fails silently if session cookies or session tracking is disabled.
	 *
	 * Allows the session to be set even if the same customer is already set.
	 * Doing this will extend the cookie expiry date.
	 *
	 * @param Customer $customer
	 * @param string   $language
	 *
	 * @since 4.0
	 */
	public static function set_session_customer( $customer, $language = '' ) {
		if ( headers_sent() || ! self::cookies_permitted() || ! $customer ) {
			return;
		}

		if ( is_user_logged_in() ) {
			return; // session for logged in user will already be set
		}

		$key = $customer->get_key();

		$customer->update_language( $language ? $language : Language::get_current() );

		self::set_tracking_key_to_be_set( $key );
	}


	/**
	 * Returns the current session customer and takes into account session tracking cookies.
	 *
	 * @return Customer|false
	 */
	public static function get_session_customer() {
		if ( is_user_logged_in() ) {
			return aw_get_logged_in_customer();
		}

		if ( ! Options::session_tracking_enabled() ) {
			return false;
		}

		$cookie_key = self::get_current_tracking_key();
		// uses the newly set key if it exists and can be set
		if ( $cookie_key ) {
			return Customer_Factory::get_by_key( $cookie_key );
		}

		return false;
	}


	/**
	 * Returns true if the supplied $customer arg matches the currently tracked session customer.
	 *
	 * @since 4.3.0
	 *
	 * @param Customer|int $input_customer
	 *
	 * @return bool
	 */
	public static function is_session_customer( $input_customer ) {
		$session_customer = self::get_session_customer();
		if ( ! $session_customer ) {
			return false;
		}

		if ( is_a( $input_customer, 'AutomateWoo\Customer' ) ) {
			return $session_customer->get_id() === $input_customer->get_id();
		} elseif ( is_numeric( $input_customer ) ) {
			return $session_customer->get_id() === Clean::id( $input_customer );
		}

		return false;
	}
}
