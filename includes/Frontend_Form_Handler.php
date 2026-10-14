<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Frontend_Form_Handler
 *
 * @since 3.9
 */
class Frontend_Form_Handler {

	/** @var string */
	public static $current_action = '';


	/** @var string[] */
	private static $actions = [
		'automatewoo_save_communication_preferences',
		'automatewoo_save_communication_signup',
	];

	/**
	 * Maximum signup submissions allowed from one source within the window.
	 *
	 * @var int
	 */
	private const SIGNUP_SUBMISSION_LIMIT = 10;

	/**
	 * Maximum signup submissions allowed from all sources combined within the window.
	 *
	 * @var int
	 */
	private const SIGNUP_GLOBAL_LIMIT = 200;

	/**
	 * Length of the signup submission window, in seconds.
	 *
	 * @var int
	 */
	private const SIGNUP_SUBMISSION_WINDOW = 15 * MINUTE_IN_SECONDS;



	/**
	 * Handle frontend form post
	 */
	public static function handle() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Frontend_Form_Handler::handle() via wp_verify_nonce() before dispatch.
		$action              = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
		$honeypot_field_name = apply_filters( 'automatewoo/honeypot_field/name', 'firstname' );

		if ( ! in_array( $action, self::$actions, true ) || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $action ) ) {
			return;
		}

		if ( ! empty( $_POST[ $honeypot_field_name ] ) ) {
			wc_add_notice(
				sprintf(
					/* translators: %s Error code when form can not be submitted. */
					__( 'The form could not be submitted. Error code: %s', 'automatewoo' ),
					1
				),
				'error'
			);
			return;
		}

		$action               = str_replace( 'automatewoo_', '', $action );
		self::$current_action = $action;

		nocache_headers();

		call_user_func( [ __CLASS__, $action ] );
	}



	/**
	 * Save communication preferences for an existing customer identified by an
	 * `aw_token` or, behind the `automatewoo/preferences/accept_customer_key`
	 * filter, a `customer_key`.
	 *
	 * Per rule R3, a token with any non-empty bound intent (e.g. `unsubscribe`,
	 * `tracking-opt-out`) is rejected outright: those tokens authorize only
	 * their own single action, not a write to the full management form. That
	 * rejection is an explicit refusal, so such a request never falls through
	 * to the `customer_key` path either.
	 *
	 * @since 6.9.1 Accepts `aw_token`; rejects any non-empty-intent token.
	 * @since 6.9.1 Every path that drops the submitted changes queues an error
	 *              notice — a silent reload reads as a successful save.
	 * @since 6.9.1 While someone is logged in, a credential that resolves to a
	 *              different customer is refused, matching the render path
	 *              (Communication_Page::output_preferences_shortcode_for_logged_in_user()):
	 *              the credential is a bearer token that works logged out anyway,
	 *              so this is no escalation — but the two surfaces must state one
	 *              rule, and a refusal is never silent.
	 */
	public static function save_communication_preferences() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Frontend_Form_Handler::handle() via wp_verify_nonce() before dispatch.
		$token = isset( $_POST['aw_token'] ) ? sanitize_text_field( wp_unslash( $_POST['aw_token'] ) ) : '';

		$customer      = false;
		$token_expired = false;

		if ( $token ) {
			// Expiry is deliberately tolerated while *inspecting* the token:
			// rule R3 refuses any authentic token bound to a non-empty intent,
			// expired or not, without downgrading to the key fallback below.
			// Acceptance as a credential still requires a live token.
			$decoded = Preferences_Token::validate_allowing_expired( $token );

			if ( $decoded && '' !== $decoded['intent'] ) {
				// The submitted changes are dropped, so say so — a silent
				// reload would leave the customer believing they saved.
				wc_add_notice( __( "Your changes couldn't be saved because this link can't be used to update preferences. Sign in to your account to manage them.", 'automatewoo' ), 'error' );
				return;
			}

			if ( $decoded && ! $decoded['expired'] ) {
				$customer = $decoded['customer'];
			} elseif ( $decoded ) {
				$token_expired = true;
			}
		}

		if ( ! $customer && apply_filters( 'automatewoo/preferences/accept_customer_key', true ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Frontend_Form_Handler::handle() via wp_verify_nonce() before dispatch.
			$customer = isset( $_POST['customer_key'] ) ? Customer_Factory::get_by_key( sanitize_text_field( wp_unslash( $_POST['customer_key'] ) ) ) : false;

			if ( $customer ) {
				Customer_Key_Redemption_Counter::record();
			}
		}

		if ( ! $customer ) {
			if ( $token_expired ) {
				// Authentic token, but past its lifetime and with no other
				// accepted credential — the changes were not saved.
				wc_add_notice( __( "Your changes couldn't be saved because this link has expired. Sign in to your account to update your preferences.", 'automatewoo' ), 'error' );
			} else {
				// No accepted credential at all: the token failed authentication
				// (rotated, tampered with, or minted under a different site) or the
				// posted key resolved nobody. We can't know which, so don't claim
				// it expired — only that the link no longer works, and what to do
				// instead. A silent reload would read as a successful save.
				wc_add_notice( __( "Your changes couldn't be saved because this link can no longer be used to update your preferences. Sign in to your account, or use a link from a more recent email.", 'automatewoo' ), 'error' );
			}
			return;
		}

		if ( is_user_logged_in() ) {
			// Same rule as the render path: while someone is logged in, a credential
			// belonging to a different customer must not act on that customer's data.
			// The other customer's address is not disclosed — the person holding the
			// link may not be its owner. Fail closed when the login resolves nobody.
			$logged_in_customer = Customer_Factory::get_by_user_id( get_current_user_id() );

			if ( ! $logged_in_customer || (int) $logged_in_customer->get_id() !== (int) $customer->get_id() ) {
				wc_add_notice( __( "Your changes couldn't be saved because this link is for a different email address. To use it, sign out first.", 'automatewoo' ), 'error' );
				return;
			}
		}

		// A submitted credential authorises this write; it does not establish that the
		// visitor owns the identity, so it must not start a tracking session for them.
		self::update_customer_preferences( $customer, false );

		// Starting a session used to refresh the stored language as a side effect.
		// This path no longer starts one, so the refresh happens here instead — and
		// only here: the credential proves the holder may edit this customer's
		// preferences, which is what the language belongs to.
		self::maybe_refresh_customer_language( $customer );

		wc_add_notice( __( 'Your communication preferences were updated.', 'automatewoo' ) );
	}



	/**
	 * Save communication preferences for a new signup identified by email.
	 */
	public static function save_communication_signup() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Frontend_Form_Handler::handle() via wp_verify_nonce() before dispatch.
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		// Reject a malformed address before any lookup.
		if ( ! is_email( $email ) ) {
			wc_add_notice( __( 'Please enter a valid email address.', 'automatewoo' ), 'error' );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Frontend_Form_Handler::handle() via wp_verify_nonce() before dispatch.
		$subscribing = isset( $_POST['subscribe'] );

		// Double opt-in path: hold the subscribe pending an emailed confirmation.
		// Check the throttle first so it counts every submission, not only the
		// ones that reach the record lookup below.
		if ( $subscribing && Optin_Confirmation::is_required() ) {
			if ( ! self::count_and_check_signup_rate_limit() ) {
				$customer = Customer_Factory::get_by_email( $email );
				if ( $customer && ! $customer->get_is_subscribed() ) {
					Optin_Confirmation::start( $customer );
				}
			}

			wc_add_notice( __( "Thanks! We've received your request. If your subscription still needs confirming, we've emailed you a link to complete it.", 'automatewoo' ) );
			return;
		}

		// Immediate path: an opt-out, or a site with confirmation turned off.
		// Everything here is gated by the throttle.
		if ( ! self::count_and_check_signup_rate_limit() ) {
			$existing = Customer_Factory::get_by_email( $email, false );

			if ( ! $existing && ! get_user_by( 'email', $email ) ) {
				// Only an address with no customer record and no account is written.
				$customer = Customer_Factory::get_by_email( $email );

				if ( $customer ) {
					// A signup neither starts visitor session tracking nor refreshes
					// the stored language: a typed email is a claim anyone can make,
					// and the address may resolve to an account that isn't the
					// visitor's. The preferences form refreshes the language itself,
					// behind the conditions the session promotion used to apply.
					self::update_customer_preferences( $customer, false );
				}
			} elseif ( $existing && ! $subscribing ) {
				// An opt-out invalidates any pending confirmation for an existing
				// address, without otherwise changing its preferences.
				Optin_Confirmation::clear_pending( $existing );
			}
		}

		// The confirmation reflects only the visitor's own subscribe choice, never
		// whether the address was already known.
		if ( $subscribing ) {
			wc_add_notice( __( "Thanks! We've received your request to receive marketing communications.", 'automatewoo' ) );
		} else {
			wc_add_notice( __( "Thanks! We've received your request to stop receiving marketing communications.", 'automatewoo' ) );
		}
	}


	/**
	 * Count the current submission against the per-source limit and the site-wide ceiling.
	 *
	 * Counts are approximate; see Rate_Limiter.
	 *
	 * @return bool True when the submission is over a limit.
	 */
	private static function count_and_check_signup_rate_limit() {
		/**
		 * Filters the maximum signup submissions allowed from all sources within the window.
		 *
		 * @since 6.9.3
		 *
		 * @param int $limit Site-wide limit. Use 0 to disable.
		 */
		$global_limit = (int) apply_filters( 'automatewoo/frontend_form_handler/signup_global_limit', self::SIGNUP_GLOBAL_LIMIT );

		return Rate_Limiter::is_over_limit( 'signup', self::SIGNUP_SUBMISSION_LIMIT, self::SIGNUP_SUBMISSION_WINDOW, $global_limit, 'communication-signup' );
	}


	/**
	 * @param Customer $customer
	 * @param bool     $set_session Whether to attach the customer to the visitor's tracking session.
	 */
	protected static function update_customer_preferences( $customer, $set_session = true ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Frontend_Form_Handler::handle() via wp_verify_nonce() before dispatch.
		if ( isset( $_POST['subscribe'] ) ) {
			$customer->opt_in();
		} else {
			$customer->opt_out( Clean::id( aw_request( 'workflow' ) ) );
		}

		self::update_customer_tracking_preference( $customer );

		if ( $set_session ) {
			Session_Tracker::set_session_customer( $customer );
		}

		do_action( 'automatewoo/communication_page/save_preferences', $customer );
	}

	/**
	 * Refresh the customer's stored language to the language of the current request.
	 *
	 * Session_Tracker::set_session_customer() used to do this as a side effect of the
	 * preferences form starting a tracking session. The form no longer starts one, so
	 * the preferences path calls this instead, behind the same conditions the tracker
	 * applies: a submitted credential is not proof of identity ownership, so it must
	 * not overwrite a logged-in customer's language, and this must not run on a store
	 * that has opted out of tracking.
	 *
	 * The signup path deliberately never calls this — a typed email may resolve to
	 * someone else's account, and their stored language isn't the visitor's to change.
	 *
	 * @since 6.9.1
	 *
	 * @param Customer $customer
	 */
	protected static function maybe_refresh_customer_language( $customer ) {
		if ( headers_sent() || ! Session_Tracker::cookies_permitted() || is_user_logged_in() ) {
			return;
		}

		$customer->update_language( Language::get_current() );
	}

	/**
	 * Save the tracking preference, but only from a form that actually rendered the control.
	 *
	 * A theme override of the preferences template will not have the new block, and a
	 * missing checkbox must never be read as "opt me out".
	 *
	 * @since 6.9.1
	 *
	 * @param Customer $customer
	 */
	protected static function update_customer_tracking_preference( $customer ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Frontend_Form_Handler::handle() via wp_verify_nonce() before dispatch.
		if ( ! isset( $_POST['tracking_preference'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Frontend_Form_Handler::handle() via wp_verify_nonce() before dispatch.
		if ( isset( $_POST['allow_tracking'] ) ) {
			$customer->opt_in_to_tracking();
		} else {
			$customer->opt_out_of_tracking();
		}
	}
}
