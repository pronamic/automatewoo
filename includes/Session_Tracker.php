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

	/**
	 * Option storing the timestamp signed tracking cookies were first used on this site.
	 * Anchors the acceptance window for legacy unsigned cookies.
	 *
	 * @since 6.9.1
	 * @var string
	 */
	const SIGNED_COOKIE_SINCE_OPTION = 'automatewoo_signed_tracking_cookie_since';

	/**
	 * Days after deploy during which legacy unsigned tracking cookies are still accepted.
	 *
	 * The window equals the tracking cookie's own lifetime (the unfiltered default of
	 * `automatewoo_visitor_tracking_cookie_expiry`): by the time it closes, every unsigned
	 * cookie has either been upgraded to a signed one (the visitor returned at least once)
	 * or expired naturally (they did not), so no visitor is silently dropped mid-cookie-life.
	 * When the expiry filter is in effect the window follows the filtered value instead —
	 * see get_unsigned_cookie_window_days().
	 *
	 * @since 6.9.1
	 * @var int
	 */
	const UNSIGNED_COOKIE_WINDOW_DAYS = 365;

	/**
	 * Anchor value recorded on a first install so the legacy unsigned-cookie acceptance
	 * window is closed from the start: a fresh site has never issued an unsigned tracking
	 * cookie, so there is nothing to stay compatible with. This sits far enough in the
	 * past (epoch + 1s) that the window has always already elapsed, yet is truthy so
	 * is_unsigned_cookie_accepted() treats the anchor as recorded rather than falling
	 * back to the no-anchor default.
	 *
	 * @since 6.9.1
	 * @var int
	 */
	const UNSIGNED_COOKIE_WINDOW_CLOSED_ANCHOR = 1;

	/**
	 * Marker segment carried by a signed cookie value that was upgraded from a legacy
	 * unsigned cookie (`{key}.u.{sig}`). The server signed the value, but it only ever
	 * inherited the key from a bare cookie it could not verify, so the identity stays
	 * in the weaker capture-only class across requests: it may create a stored cart
	 * and update the cart it created itself, but never counts as verified. The marker
	 * is part of the HMAC payload, so it can be neither stripped nor forged. It is
	 * shed only when a genuine identity event (login, server-side resolution) issues
	 * a fresh unmarked cookie.
	 *
	 * @since 6.9.1
	 * @var string
	 */
	const UPGRADED_COOKIE_MARKER = 'u';

	/**
	 * Provenance for set_session_customer(): the customer was resolved from a genuinely
	 * verified event — a user login or an order they placed.
	 *
	 * @since 6.9.1
	 * @var string
	 */
	const PROVENANCE_VERIFIED = 'verified';

	/**
	 * Provenance for set_session_customer(): the customer was resolved only from an email
	 * address the visitor typed (comment form, Store API checkout draft, third-party capture).
	 *
	 * @since 6.9.1
	 * @var string
	 */
	const PROVENANCE_CAPTURED_EMAIL = 'captured_email';

	/**
	 * Provenance for set_session_customer(): the customer is an existing guest whose email
	 * the visitor typed, and the visitor holds no verified identity for that guest. The
	 * identity lasts for the current request only: it never receives a tracking cookie,
	 * check-in or language update.
	 *
	 * @since 6.9.3
	 * @var string
	 */
	const PROVENANCE_CAPTURE_ONLY = 'capture_only';

	/**
	 * WooCommerce session key holding the tracking key of the guest this session created
	 * from a typed email. It lives in the server-side WC session, never in a browser
	 * cookie of ours, so a typed email can not change any AutomateWoo cookie.
	 *
	 * @since 6.9.3
	 * @var string
	 */
	const SESSION_TRACKING_KEY = 'automatewoo_tracking';

	/**
	 * WC session key that holds the server-side stop of cookie-based tracking.
	 *
	 * @since 6.9.3
	 */
	const SESSION_TRACKING_STOPPED_KEY = 'automatewoo_tracking_stopped';

	/**
	 * Typed-email claims one source can make per window.
	 *
	 * @since 6.9.3
	 * @var int
	 */
	private const CAPTURED_EMAIL_CLAIM_LIMIT = 20;

	/**
	 * Typed-email claims all sources together can make per window.
	 *
	 * @since 6.9.3
	 * @var int
	 */
	private const CAPTURED_EMAIL_CLAIM_GLOBAL_LIMIT = 500;

	/**
	 * Length of the typed-email claim window, in seconds.
	 *
	 * @since 6.9.3
	 * @var int
	 */
	private const CAPTURED_EMAIL_CLAIM_WINDOW = 15 * MINUTE_IN_SECONDS;

	/** @var int (days) */
	private static $tracking_cookie_expiry;

	/** @var string cookie name */
	private static $tracking_key_cookie_name;

	/** @var string - This key WILL BE saved */
	private static $tracking_key_to_set = '';

	/**
	 * Tracking key whose identity was resolved server-side during this request
	 * (user login, placed order, captured email) rather than read from the cookie.
	 *
	 * @since 6.9.1
	 * @var string
	 */
	private static $verified_tracking_key = '';

	/**
	 * Tracking key whose only evidence is a cookie the server inherited rather than
	 * issued from a verified event: a legacy unsigned value accepted during the
	 * deprecation window, or the marked (`{key}.u.{sig}`) cookie such a value was
	 * upgraded to on an earlier request. Byte-for-byte, a legacy value is identical
	 * to a forged one, so this key must never count as a verified identity — it may
	 * only capture (see is_customer_identity_from_legacy_cookie()).
	 *
	 * @since 6.9.1
	 * @var string
	 */
	private static $unverified_upgraded_key = '';

	/**
	 * Tracking key of a registered customer who was resolved during this request only
	 * from an email address the visitor typed. A typed email is a claim, not proof of
	 * identity, so this key is tracked separately from $verified_tracking_key.
	 *
	 * @since 6.9.1
	 * @var string
	 */
	private static $captured_email_tracking_key = '';

	/**
	 * Tracking key of a guest this session created from a typed email, set in the request
	 * that created it (whether or not cookie consent allows storing it in the WC session).
	 *
	 * @since 6.9.3
	 * @var string
	 */
	private static $session_owned_tracking_key = '';


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
	 * @since 6.9.1 Cookie values are HMAC-signed (`{key}.{sig}`). This method verifies the
	 *              signature and always returns the bare key. A value carrying an invalid
	 *              signature clears the tracking cookies (a signature can only turn
	 *              invalid for good — forgery or a signing-secret change — and a dead
	 *              cookie would otherwise block a fresh key from ever being issued) and
	 *              returns an empty string. A legacy unsigned value is accepted
	 *              during the deprecation window (see is_unsigned_cookie_accepted()) and
	 *              transparently queued for a re-signed cookie write.
	 *
	 * @return string The bare, verified tracking key, or an empty string.
	 */
	public static function get_tracking_cookie() {
		$value = Clean::string( Cookies::get( self::$tracking_key_cookie_name ) );

		if ( ! $value ) {
			return '';
		}

		$parsed = self::parse_tracking_cookie_value( $value );

		if ( false === $parsed ) {
			// The signature does not verify: forged, corrupt, or signed under a
			// different secret — which is every visitor's cookie at once after a
			// wp_salt() rotation, a migration, or a restore against other salts.
			// Such a value can never become valid again, so clear both tracking
			// cookies like the unsigned branch below does — otherwise, when
			// wp_automatewoo_session_started is already set, the cleanup at the end
			// of maybe_set_session_cookies() is skipped and no fresh key is ever
			// issued for the rest of the browser session. Clearing also empties
			// $_COOKIE, so a later read this request returns early instead of
			// re-clearing.
			self::forget_dead_tracking_cookie();
			return '';
		}

		if ( ! $parsed['signed'] ) {
			if ( ! self::is_unsigned_cookie_accepted() ) {
				// The window has closed: this legacy value is worthless. Clear both
				// tracking cookies so a dead value can't strand the visitor — otherwise,
				// when wp_automatewoo_session_started is already set, the cleanup at the
				// end of maybe_set_session_cookies() is skipped and no fresh key is ever
				// issued. Clearing lets the next request start a new session.
				self::forget_dead_tracking_cookie();
				return '';
			}

			// Transparently upgrade the legacy cookie: queue a re-signed write via the
			// header-timing-safe deferred path. The rewrite carries the upgraded marker
			// (see maybe_set_session_cookies()), so the identity stays capture-only on
			// every later request too — a legacy value is indistinguishable from a
			// forged one, and re-signing it must not launder it into a verified one.
			self::$unverified_upgraded_key = $parsed['key'];

			if ( ! self::$tracking_key_to_set ) {
				self::set_tracking_key_to_be_set( $parsed['key'] );
			}
		} elseif ( $parsed['upgraded'] ) {
			// A marked cookie the server issued for a legacy value on an earlier
			// request: still only inherited evidence, so still capture-only.
			self::$unverified_upgraded_key = $parsed['key'];
		}

		return $parsed['key'];
	}


	/**
	 * Parses a raw tracking cookie value into a key, signature state and upgrade marker.
	 *
	 * Tracking keys generated by aw_generate_key() are strictly alphanumeric, so any
	 * value containing a `.` must be a signed value — a dotted value whose signature
	 * fails verification is forged or corrupt and is rejected outright, never treated
	 * as a legacy unsigned key. A signed value is `{key}.{sig}` or, when the cookie
	 * was upgraded in place from a legacy unsigned one, `{key}.u.{sig}`; the marker
	 * is bound into the HMAC payload, so stripping it or grafting it on invalidates
	 * the signature.
	 *
	 * @since 6.9.1
	 *
	 * @param string $value Raw cookie value.
	 *
	 * @return array|false Array with `key` (string), `signed` (bool) and `upgraded`
	 *                     (bool), or false when the value carries an invalid signature.
	 */
	private static function parse_tracking_cookie_value( $value ) {
		$pos = strrpos( $value, '.' );

		if ( false === $pos ) {
			return [
				'key'      => $value,
				'signed'   => false,
				'upgraded' => false,
			];
		}

		$payload = substr( $value, 0, $pos );
		$sig     = substr( $value, $pos + 1 );

		$upgraded = false;
		$key      = $payload;

		if ( substr( $payload, -2 ) === '.' . self::UPGRADED_COOKIE_MARKER ) {
			$upgraded = true;
			$key      = substr( $payload, 0, -2 );
		}

		if ( '' !== $key && hash_equals( self::sign_tracking_key( $key, $upgraded ), $sig ) ) {
			return [
				'key'      => $key,
				'signed'   => true,
				'upgraded' => $upgraded,
			];
		}

		return false;
	}


	/**
	 * Computes the HMAC signature for a tracking key.
	 *
	 * The secret is derived from `wp_salt( 'auth' )` under a purpose label distinct
	 * from the preferences token's, so the two credentials can never substitute for
	 * one another. The blog ID is bound into the signed payload because `wp_salt()`
	 * is network-wide on multisite, so without it a signature minted on one site of
	 * a network would verify on every other site. The upgraded marker is bound in
	 * too, so a capture-only cookie's signature can never validate an unmarked
	 * (fully verified) value or vice versa.
	 *
	 * @since 6.9.1
	 *
	 * @param string $key      Bare tracking key.
	 * @param bool   $upgraded Whether the value carries the upgraded (capture-only) marker.
	 *
	 * @return string 64-character hex sha256 HMAC.
	 */
	private static function sign_tracking_key( $key, $upgraded = false ) {
		$secret = hash_hmac( 'sha256', 'automatewoo-tracking-cookie', wp_salt( 'auth' ) );
		$marker = $upgraded ? self::UPGRADED_COOKIE_MARKER : '';

		return hash_hmac( 'sha256', $key . '|' . $marker . '|' . get_current_blog_id(), $secret );
	}


	/**
	 * Builds the full tracking cookie value for a key: `{key}.{sig}`, or
	 * `{key}.u.{sig}` for a capture-only upgraded value.
	 *
	 * @since 6.9.1
	 *
	 * @param string $key      Bare tracking key.
	 * @param bool   $upgraded Whether to mark the value as upgraded (capture-only).
	 *
	 * @return string
	 */
	private static function build_tracking_cookie_value( $key, $upgraded = false ) {
		$marker = $upgraded ? '.' . self::UPGRADED_COOKIE_MARKER : '';

		return $key . $marker . '.' . self::sign_tracking_key( $key, $upgraded );
	}


	/**
	 * Whether a legacy unsigned tracking cookie value is still accepted.
	 *
	 * Acceptance is time-boxed server-side: the window is anchored to the deploy
	 * timestamp Installer::install() records, and unsigned values are rejected
	 * once the window has elapsed, so the deprecation window closes on a concrete
	 * date even if no one flips the filter. The window spans one full cookie lifetime, so every unsigned cookie
	 * still in circulation has either been transparently upgraded to a signed one
	 * or expired naturally by the time the window closes.
	 *
	 * @since 6.9.1
	 *
	 * @return bool
	 */
	private static function is_unsigned_cookie_accepted() {
		$since = (int) get_option( self::SIGNED_COOKIE_SINCE_OPTION );

		if ( $since ) {
			$window_open = time() < $since + self::get_unsigned_cookie_window_days() * DAY_IN_SECONDS;
		} else {
			// No anchor recorded yet — the installer hasn't run. Never write the
			// anchor from this read path: it runs on unauthenticated front-end
			// requests carrying an attacker-settable cookie, and on a fresh site
			// activated by WP-CLI or a provisioner (where admin_init hasn't run)
			// a lazy write would let the first forged request open the window for
			// its full length. The installer is the sole authority; until it
			// records the anchor, the database version decides: an upgraded site
			// (version present) has real legacy unsigned cookies in circulation,
			// so its window must already be open or returning visitors would have
			// their live cookies cleared as worthless; a first install (no
			// version yet) has never issued an unsigned cookie, so the window is
			// closed from the very first request.
			$window_open = (bool) Options::database_version();
		}

		/**
		 * Filters whether legacy unsigned tracking cookie values are accepted.
		 *
		 * The default is true until the server-side deprecation window elapses,
		 * then false. The window equals the tracking cookie lifetime (365 days by
		 * default, following the `automatewoo_visitor_tracking_cookie_expiry`
		 * filter), so by the time it closes every unsigned cookie has either been
		 * upgraded on a return visit or expired naturally.
		 * Return false to end the window early, or true to extend it.
		 *
		 * @since 6.9.1
		 *
		 * @param bool $accept Whether unsigned cookie values are accepted.
		 */
		return (bool) apply_filters( 'automatewoo/tracking/accept_unsigned_cookie', $window_open );
	}


	/**
	 * Length of the unsigned-cookie acceptance window, in days.
	 *
	 * Derived from the configured tracking cookie lifetime so the two never drift
	 * apart; UNSIGNED_COOKIE_WINDOW_DAYS is the fallback when init() has not run.
	 *
	 * @since 6.9.1
	 *
	 * @return int
	 */
	private static function get_unsigned_cookie_window_days() {
		return self::$tracking_cookie_expiry ? (int) self::$tracking_cookie_expiry : self::UNSIGNED_COOKIE_WINDOW_DAYS;
	}


	/**
	 * Whether the given customer's identity is verified for the current request.
	 *
	 * True when the customer is the logged-in user, when the customer was resolved
	 * server-side during this request from a genuinely verified event (user login,
	 * placed order), or when the tracking cookie carries the customer's key with a
	 * valid signature and no upgraded marker. A bare (unsigned) cookie value is never
	 * proof of identity, even after it has been queued for a re-signed write this
	 * request, and neither is the marked (`{key}.u.{sig}`) cookie that write produces:
	 * the server signed it, but only ever inherited the key from a value it could not
	 * verify, so the marker keeps the identity capture-only across requests — see
	 * is_customer_identity_from_legacy_cookie(). A registered customer resolved only
	 * from a typed email address is likewise never verified — see
	 * is_customer_identity_from_captured_email().
	 *
	 * @since 6.9.1
	 *
	 * @param Customer|false $customer
	 *
	 * @return bool
	 */
	public static function is_customer_identity_verified( $customer ) {
		if ( ! $customer instanceof Customer ) {
			return false;
		}

		if ( is_user_logged_in() && $customer->get_user_id() === get_current_user_id() ) {
			return true;
		}

		$key = (string) $customer->get_key();

		if ( ! $key ) {
			return false;
		}

		if ( self::$verified_tracking_key && hash_equals( self::$verified_tracking_key, $key ) ) {
			return true;
		}

		if ( self::$unverified_upgraded_key && hash_equals( self::$unverified_upgraded_key, $key ) ) {
			return false;
		}

		$value = Clean::string( Cookies::get( self::$tracking_key_cookie_name ) );

		if ( ! $value ) {
			return false;
		}

		$parsed = self::parse_tracking_cookie_value( $value );

		return false !== $parsed && $parsed['signed'] && ! $parsed['upgraded'] && hash_equals( $parsed['key'], $key );
	}


	/**
	 * Whether the given customer's identity for this request rests only on a tracking
	 * cookie the server inherited rather than issued: a legacy unsigned value accepted
	 * during the deprecation window, or the marked (`{key}.u.{sig}`) cookie such a
	 * value was upgraded to on an earlier request.
	 *
	 * A legacy value is byte-identical to a forged one, so this identity class may
	 * capture — create a stored cart, and keep updating the cart it created itself —
	 * exactly like a captured-email identity, but may never overwrite or delete a
	 * cart authored by anything else. It sheds the class only through a genuine
	 * identity event (login, server-side resolution), which issues a fresh unmarked
	 * cookie.
	 *
	 * @since 6.9.1
	 *
	 * @param Customer|false $customer
	 *
	 * @return bool
	 */
	public static function is_customer_identity_from_legacy_cookie( $customer ) {
		if ( ! $customer instanceof Customer ) {
			return false;
		}

		// Populates $unverified_upgraded_key when the cookie is a bare legacy value
		// or a marked upgraded one, and handles the acceptance window and clearing.
		self::get_tracking_cookie();

		if ( ! self::$unverified_upgraded_key ) {
			return false;
		}

		$key = (string) $customer->get_key();

		return $key && hash_equals( self::$unverified_upgraded_key, $key );
	}


	/**
	 * Whether the given registered customer's identity for this request came only from
	 * an email address rather than a login or the account's own order (guest order
	 * billing email, or an add-on calling set_session_customer() with the default
	 * provenance).
	 *
	 * Such an identity is an unverified claim: anyone can type anyone's address. It may
	 * create a stored cart for the customer and keep updating a cart it created itself,
	 * but is never given a signed tracking cookie and cannot touch any other cart —
	 * including every cart that predates this protection.
	 *
	 * @since 6.9.1
	 * @since 6.9.3 Emails typed into the comment form, Store API checkout draft and
	 *              presubmit capture no longer produce such an identity for a registered
	 *              customer; see set_session_by_captured_email().
	 *
	 * @param Customer|false $customer
	 *
	 * @return bool
	 */
	public static function is_customer_identity_from_captured_email( $customer ) {
		if ( ! $customer instanceof Customer ) {
			return false;
		}

		$key = (string) $customer->get_key();

		if ( ! $key ) {
			return false;
		}

		if ( self::$captured_email_tracking_key && hash_equals( self::$captured_email_tracking_key, $key ) ) {
			return true;
		}

		// The guest this WC session created from a typed email: a claim, never verified.
		$session_key = self::get_session_tracking_key();

		return $session_key && hash_equals( $session_key, $key ) && ! self::is_customer_identity_verified( $customer );
	}


	/**
	 * Whether the customer was resolved only from a typed email AND is not a guest this
	 * session created itself (another guest's email, or a registered account). Only such
	 * an identity is barred from writing profile data.
	 *
	 * @since 6.9.3
	 * @internal
	 *
	 * @param Customer|false $customer
	 *
	 * @return bool
	 */
	public static function is_customer_identity_capture_only( $customer ) {
		if ( ! self::is_customer_identity_from_captured_email( $customer ) ) {
			return false;
		}

		$key         = (string) $customer->get_key();
		$session_key = self::get_session_tracking_key();

		return ! ( self::$session_owned_tracking_key && hash_equals( self::$session_owned_tracking_key, $key ) )
			&& ! ( $session_key && hash_equals( $session_key, $key ) );
	}


	/**
	 * This method doesn't actually set the cookie, rather it initiates the cookie setting.
	 * Cookies are set only on 'wp' or 'shutdown', and only for verified events (login, an
	 * order placed by the browser's own identity). A typed email never queues a cookie: the
	 * guest it creates is remembered in the WC session instead, see store_session_tracking_key().
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
	 * Drops a tracking cookie that failed verification, without touching any
	 * request-level identity state.
	 *
	 * This is the clear used by the read path (get_tracking_cookie()), which is
	 * reached as late as `shutdown` via Carts::maybe_store_cart(). Two things
	 * distinguish it from clear_tracking_cookies():
	 *
	 * - After headers are sent the browser cookie cannot be expired
	 *   (wc_setcookie() would only raise an E_USER_NOTICE and change nothing), so
	 *   only the in-request $_COOKIE copies are dropped — later reads this
	 *   request return early instead of re-parsing, and the next request reads
	 *   the same dead value before headers are sent and clears the browser
	 *   cookie then, so nobody is stranded.
	 * - It never resets $verified_tracking_key, $captured_email_tracking_key or
	 *   $tracking_key_to_set. Those record an identity resolved server-side
	 *   earlier in the same request (login, placed order, captured email), and a
	 *   cookie that fails verification is no evidence against them. Resetting
	 *   them from a getter silently revoked provenance recorded by
	 *   set_session_customer() and wiped the very replacement key whose issuance
	 *   the clear exists to unblock.
	 *
	 * @since 6.9.1
	 */
	private static function forget_dead_tracking_cookie() {
		if ( headers_sent() ) {
			unset( $_COOKIE[ self::$tracking_key_cookie_name ], $_COOKIE['wp_automatewoo_session_started'] );
			return;
		}

		Cookies::clear( self::$tracking_key_cookie_name );
		Cookies::clear( 'wp_automatewoo_session_started' );
	}


	/**
	 * Clear session cookies
	 *
	 * @since 4.3
	 */
	public static function clear_tracking_cookies() {
		self::clear_session_tracking_key();
		self::clear_tracking_stopped();
		self::$tracking_key_to_set         = '';
		self::$verified_tracking_key       = '';
		self::$unverified_upgraded_key     = '';
		self::$captured_email_tracking_key = '';
		self::$session_owned_tracking_key  = '';
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
		self::clear_session_tracking_key();
		self::clear_tracking_stopped();
		// A live login is genuine verification, so opt in to the verified class.
		self::set_session_customer( $new_customer, '', self::PROVENANCE_VERIFIED );
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

		// Read the tracking cookie before the write block below: parsing a legacy
		// unsigned value queues its re-signed rewrite into $tracking_key_to_set, and
		// that must happen before the write or the upgrade would never be written —
		// no earlier code reads the cookie on an ordinary logged-out page request,
		// and by the shutdown re-run headers are already sent.
		self::get_tracking_cookie();

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
			} elseif ( self::$unverified_upgraded_key && hash_equals( self::$unverified_upgraded_key, (string) $logged_in_customer->get_key() ) ) {
				// The browser holds a capture-only (legacy or upgraded-marked) cookie
				// for the very account that is now logged in: the login is genuine
				// verification, so replace the cookie with a fully verified one.
				$cookie_needs_updating = true;
			}
		}

		if ( $cookie_needs_updating && $logged_in_customer ) {
			// A live login vouches for the key, so the write below is unmarked.
			self::$verified_tracking_key = $logged_in_customer->get_key();
			self::$tracking_key_to_set   = $logged_in_customer->get_key();
		}

		// Set the tracking cookie if one needs setting.
		// The value is signed so the key can be verified when it is read back.
		// The transparent rewrite of a legacy unsigned cookie keeps the upgraded
		// marker unless a verified event vouched for the key this request: the
		// server only inherited that key, so the re-signed cookie must stay in
		// the capture-only class on every later request too.
		if ( self::$tracking_key_to_set ) {
			$is_upgrade_only = self::$unverified_upgraded_key
				&& hash_equals( self::$unverified_upgraded_key, self::$tracking_key_to_set )
				&& ! ( self::$verified_tracking_key && hash_equals( self::$verified_tracking_key, self::$tracking_key_to_set ) );

			Cookies::set(
				self::$tracking_key_cookie_name,
				self::build_tracking_cookie_value( self::$tracking_key_to_set, $is_upgrade_only ),
				time() + DAY_IN_SECONDS * self::$tracking_cookie_expiry
			);
			self::$tracking_key_to_set = false; // Don't need to set the tracking cookie again
		}

		// If a tracking cookie is set but no session started cookie, init the session now.
		// MUST not set the session cookie until we have a tracking key.
		if ( self::is_tracking_cookie_set() && ! self::is_session_started_cookie_set() ) {
			$tracking_cookie = self::get_tracking_cookie();

			// check the tracking is valid before starting a session
			if ( $tracking_cookie && Customer_Factory::get_by_key( $tracking_cookie ) ) {
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
	 * @since 6.9.1 Deletes a registered customer's stored cart only when the tracked identity
	 *              is verified — a signed tracking cookie, or an identity resolved server-side
	 *              this request (see is_customer_identity_verified()). A legacy unsigned cookie
	 *              value is byte-identical to a forged one, so it must not be able to destroy a
	 *              registered customer's cart; a verified identity keeps the pre-existing dedupe
	 *              so the customer does not receive an abandoned cart email for a cart someone
	 *              else bought on the same browser. Guest carts keep the unconditional dedupe.
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
		if ( $tracked_customer && ( ! $tracked_customer->is_registered() || self::is_customer_identity_verified( $tracked_customer ) ) ) {
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

		// The session key only ever holds a guest this WC session created or owns, so it is
		// the browser's most recent identity and wins over the cookie.
		$session_key = self::get_session_tracking_key();

		if ( $session_key ) {
			return $session_key;
		}

		if ( self::is_tracking_stopped() ) {
			return false;
		}

		return self::get_tracking_cookie();
	}


	/**
	 * Remembers the guest this WC session created from a typed email. Server-side only:
	 * no browser cookie of ours is written.
	 *
	 * @since 6.9.3
	 *
	 * @param string $key
	 */
	private static function store_session_tracking_key( $key ) {
		if ( ! WC()->session || ! self::cookies_permitted() ) {
			return;
		}

		self::maybe_start_wc_session_cookie();

		WC()->session->set(
			self::SESSION_TRACKING_KEY,
			[
				'key' => $key,
				'sid' => (string) WC()->session->get_customer_id(),
			]
		);
		self::clear_tracking_stopped();
	}


	/**
	 * Stops tracking the guest this browser's cookie identifies, server-side only: no
	 * cookie is written or expired, so the response does not depend on why tracking stopped.
	 *
	 * @since 6.9.3
	 */
	private static function stop_cookie_tracking() {
		if ( ! WC()->session || ! self::cookies_permitted() ) {
			return;
		}

		self::clear_session_tracking_key();
		WC()->session->set( self::SESSION_TRACKING_STOPPED_KEY, [ 'sid' => (string) WC()->session->get_customer_id() ] );
	}


	/**
	 * Whether the stop set by stop_cookie_tracking() applies to this WC session. Like the
	 * session key, it is ignored for another session id and in a cloned session.
	 *
	 * @since 6.9.3
	 *
	 * @return bool
	 */
	private static function is_tracking_stopped() {
		if ( ! WC()->session || ! self::cookies_permitted() ) {
			return false;
		}

		$stored = WC()->session->get( self::SESSION_TRACKING_STOPPED_KEY );

		return is_array( $stored )
			&& isset( $stored['sid'] )
			&& $stored['sid'] === (string) WC()->session->get_customer_id()
			&& ! WC()->session->get( 'previous_customer_id' );
	}


	/**
	 * @since 6.9.3
	 */
	private static function clear_tracking_stopped() {
		if ( WC()->session ) {
			WC()->session->set( self::SESSION_TRACKING_STOPPED_KEY, null );
		}
	}


	/**
	 * Gives the visitor a WooCommerce session cookie so a stored session key survives to
	 * the next request (WC saves a session only once it has its own cookie). Called the
	 * same way for every capture attempt, so the response never depends on the typed email.
	 * A Store API Cart-Token session is not a browser session and gets no cookie.
	 *
	 * @since 6.9.3
	 * @internal
	 */
	public static function maybe_start_wc_session_cookie() {
		if ( ! WC()->session instanceof \WC_Session_Handler || ! self::cookies_permitted() || headers_sent() ) {
			return;
		}

		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}


	/**
	 * Returns the key stored by store_session_tracking_key(), or an empty string.
	 *
	 * Deliberately not gated on headers_sent(): this reads server-side state and runs as
	 * late as `shutdown` (see Carts::maybe_store_cart()).
	 *
	 * @since 6.9.3
	 *
	 * @return string
	 */
	private static function get_session_tracking_key() {
		if ( ! WC()->session || ! self::cookies_permitted() ) {
			return '';
		}

		$stored = WC()->session->get( self::SESSION_TRACKING_KEY );

		if ( ! is_array( $stored ) || empty( $stored['key'] ) || ! isset( $stored['sid'] ) ) {
			return '';
		}

		// A session cloned via `?session=` copies every key and records where it came from.
		if ( $stored['sid'] !== (string) WC()->session->get_customer_id() || WC()->session->get( 'previous_customer_id' ) ) {
			return '';
		}

		return (string) $stored['key'];
	}


	/**
	 * @since 6.9.3
	 */
	private static function clear_session_tracking_key() {
		if ( WC()->session ) {
			WC()->session->set( self::SESSION_TRACKING_KEY, null );
		}
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
	 * - Email belongs to a user account = rejected, nothing is recorded or created
	 *      - Cookie customer is a different guest = clear that guest's cart and stop tracking
	 *        this browser server-side (WC session flag, no cookie written or expired)
	 * - Email matches the session customer = do nothing (check-in only when the identity is verified)
	 * - Email matches another existing guest = that guest is recorded for this request only
	 *   (capture-only): no cookie, check-in, language or meta writes, and the previous
	 *   session customer's cart is kept
	 * - Email is new
	 *      - Verified session guest
	 *          - Customer data is locked = create new customer, change cookie, clear cart from previous key to avoid duplicates
	 *          - Customer data is not locked = update customer email
	 *      - No session guest, or one that is not verified (e.g. a legacy unsigned cookie)
	 *          = create a new guest and leave the session guest untouched
	 *
	 * @since 4.3.0
	 * @since 6.9.3 An email that belongs to a user account is never resolved: no
	 *               customer record, session identity or stored cart is created for it.
	 *               A different guest tracked in this browser has its cart cleared and
	 *               tracking stops through a WC session flag, so no cookie is written or
	 *               expired. Without a WC session only the cart is cleared. An email matching another existing guest is recorded as
	 *               a capture-only identity. An unverified session guest is never renamed
	 *               or deleted.
	 *
	 * @param string $new_email
	 * @param string $language
	 *
	 * @return Customer|false
	 */
	public static function set_session_by_captured_email( $new_email, $language = '' ) {
		self::maybe_start_wc_session_cookie();

		if ( ! is_email( $new_email ) || headers_sent() || ! Options::session_tracking_enabled() ) {
			// must have a valid email, be able to set cookies, have session tracking enabled
			return false;
		}

		$new_email = Clean::email( $new_email );

		$existing_session_customer = self::get_session_customer(); // existing session customer from cookie

		if ( $existing_session_customer && $existing_session_customer->is_registered() ) {
			return $existing_session_customer; // bail if a registered user is already being tracked
		}

		$is_noop = $existing_session_customer && $existing_session_customer->get_email() === $new_email;

		if ( ! $is_noop && self::is_captured_email_claim_over_limit() ) {
			return false;
		}

		if ( get_user_by( 'email', $new_email ) ) {
			// a typed email never resolves a registered account, and it ends tracking of the previous guest
			if ( $existing_session_customer && $new_email !== $existing_session_customer->get_email() ) {
				$existing_session_customer->delete_cart();
				self::stop_cookie_tracking();
			}

			return false;
		}

		$customer_matching_email = Customer_Factory::get_by_email( $new_email, false ); // important! don't create new customer

		$is_own = $existing_session_customer && (
			self::is_customer_identity_verified( $existing_session_customer )
			|| $existing_session_customer->get_key() === self::get_session_tracking_key()
		);
		$is_new = false === $customer_matching_email;

		if ( $existing_session_customer && $existing_session_customer->get_email() === $new_email ) {
			if ( ! $is_own ) {
				// The session guest's own email, but only inherited evidence: no writes.
				if ( Options::abandoned_cart_enabled() ) {
					Carts::update_stored_customer_cart( $existing_session_customer );
				}
				return $existing_session_customer;
			}

			$new_customer = $existing_session_customer;
		} elseif ( $customer_matching_email ) {
			// Another existing guest: recorded for this request only.
			self::set_session_customer( $customer_matching_email, $language, self::PROVENANCE_CAPTURE_ONLY );

			if ( Options::abandoned_cart_enabled() ) {
				Carts::update_stored_customer_cart( $customer_matching_email );
			}

			return $customer_matching_email;
		} elseif ( $is_own ) {
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
		} else {
			// No verified session guest to build on, so create a new guest
			$new_customer = Customer_Factory::get_by_email( $new_email );
		}

		// init the new customer tracking, also saves/updates the language
		if ( $new_customer ) {
			self::set_session_customer( $new_customer, $language, self::PROVENANCE_CAPTURED_EMAIL );
		}

		$guest = $new_customer->get_guest();
		if ( $guest ) {
			$guest->do_check_in();
		}

		// update the stored cart
		if ( Options::abandoned_cart_enabled() ) {
			Carts::update_stored_customer_cart( $new_customer );
		}

		if ( $is_new && $guest ) {
			// fire hook after new email is stored
			do_action( 'automatewoo/session_tracker/new_stored_guest', $guest );
		}

		return $new_customer;
	}


	/**
	 * Counts a typed-email claim and reports whether the source is over its limit.
	 *
	 * @since 6.9.3
	 *
	 * @return bool
	 */
	private static function is_captured_email_claim_over_limit(): bool {
		/**
		 * Filters how many typed-email claims one source can make per 15 minutes.
		 *
		 * @since 6.9.3
		 *
		 * @param int $limit Default 20. Use 0 to disable the per-source limit.
		 */
		$limit = (int) apply_filters( 'automatewoo/session_tracker/captured_email_claim_limit', self::CAPTURED_EMAIL_CLAIM_LIMIT );

		/**
		 * Filters how many typed-email claims all sources together can make per 15 minutes.
		 *
		 * @since 6.9.3
		 *
		 * @param int $limit Default 500. Use 0 to disable the site-wide limit.
		 */
		$global_limit = (int) apply_filters( 'automatewoo/session_tracker/captured_email_claim_global_limit', self::CAPTURED_EMAIL_CLAIM_GLOBAL_LIMIT );

		return Rate_Limiter::is_over_limit( 'captured_email', $limit, self::CAPTURED_EMAIL_CLAIM_WINDOW, $global_limit, 'session-tracker' );
	}


	/**
	 * Store guest info if they place a comment.
	 *
	 * Only comments that are pending or approved, and sent from the store's own pages, are
	 * captured. Comments without an Origin or Referer header (apps, XML-RPC) skip capture;
	 * the comment itself is unaffected.
	 *
	 * @param int        $comment_id
	 * @param int|string $comment_approved Approval status passed by the comment_post hook.
	 */
	public static function capture_from_comment( $comment_id, $comment_approved = 1 ) {
		if ( ! in_array( $comment_approved, [ 0, '0', 1, '1' ], true ) || ! Request_Origin::is_same_site() ) {
			return;
		}

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

		$email = $order->get_billing_email();

		// WooCommerce (and Subscriptions in particular) can save the same draft order many
		// times within a single Store API request. The session and stored-cart work in
		// set_session_by_captured_email() is expensive (DB reads and writes) and only changes
		// when the captured email changes, so run it once per order + email and short-circuit
		// repeats. The checkout field capture below still runs on every save so billing values
		// added or changed on later saves are stored.
		static $session_captured = [];
		$capture_key             = $order->get_id() . '|' . $email;

		if ( array_key_exists( $capture_key, $session_captured ) ) {
			// Reuse the exact customer resolved on the first save so repeats honour the
			// registered-session-customer guard inside set_session_by_captured_email().
			$customer = $session_captured[ $capture_key ];
		} else {
			$customer                         = self::set_session_by_captured_email( $email );
			$session_captured[ $capture_key ] = $customer;
		}

		if ( $customer ) {
			// Capture the guest's data
			$guest = $customer->get_guest();
			if ( $guest && ! self::is_customer_identity_capture_only( $customer ) ) {
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
	 * @since 6.9.1 An order with no account behind it (user ID 0) identifies its
	 *              customer only by the typed billing email — and guest checkout
	 *              permits typing a registered account's address before any payment
	 *              clears — so it is promoted with captured-email provenance, never
	 *              verified. Only an order genuinely tied to a user account carries
	 *              verified provenance. This rests on WooCommerce core behaviour:
	 *              core never attaches a user account to a guest-placed order at
	 *              processing time, so a non-zero user ID here always means the
	 *              buyer was logged in. A third-party plugin that links guest
	 *              orders to existing accounts by billing email at creation time
	 *              would break that assumption and defeat this guard.
	 * @since 6.9.3 A returning guest on a new browser no longer receives a tracking
	 *              cookie from an order: a guest that already existed before the order
	 *              is promoted only when the browser already holds a verified identity
	 *              for it, otherwise it is recorded as capture-only. A guest created by
	 *              the order itself is promoted as before.
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

		if ( $order->get_user_id() ) {
			$provenance = self::PROVENANCE_VERIFIED;
		} elseif ( $customer->is_registered() ) {
			$provenance = self::PROVENANCE_CAPTURED_EMAIL;
		} elseif ( self::is_guest_created_by_order( $customer, $order ) || self::is_customer_identity_verified( $customer ) || $customer->get_key() === self::get_session_tracking_key() ) {
			$provenance = self::PROVENANCE_VERIFIED;
		} else {
			$provenance = self::PROVENANCE_CAPTURE_ONLY;
		}

		self::set_session_customer( $customer, '', $provenance );
	}


	/**
	 * Whether the guest row came into being with this order: it was created no earlier than
	 * a minute before the order and the billing email has no earlier order. Read from stored
	 * dates, so it does not depend on which listener created the guest first (block checkout
	 * creates it on the status change, before the order-processed hook).
	 *
	 * @since 6.9.3
	 *
	 * @param Customer  $customer
	 * @param \WC_Order $order
	 *
	 * @return bool
	 */
	private static function is_guest_created_by_order( $customer, $order ) {
		$guest      = $customer->get_guest();
		$ordered_at = $order->get_date_created();

		if ( ! $guest || ! $ordered_at ) {
			return false;
		}

		$created = $guest->get_date_created();

		if ( ! $created || $created->getTimestamp() < $ordered_at->getTimestamp() - MINUTE_IN_SECONDS ) {
			return false;
		}

		$earlier_orders = wc_get_orders(
			[
				'billing_email' => $order->get_billing_email(),
				'exclude'       => [ $order->get_id() ],
				'date_created'  => '<' . $ordered_at->getTimestamp(),
				'limit'         => 1,
				'return'        => 'ids',
			]
		);

		return empty( $earlier_orders );
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
	 * @param string   $provenance How the customer was resolved: self::PROVENANCE_CAPTURED_EMAIL
	 *                             (an email the visitor typed — the default),
	 *                             self::PROVENANCE_CAPTURE_ONLY (a typed email for an existing
	 *                             guest the visitor is not verified as) or
	 *                             self::PROVENANCE_VERIFIED (user login, an order placed by the
	 *                             account itself). The default is the WEAK class on purpose:
	 *                             this is a public method add-ons call with two arguments, and
	 *                             a verified identity unlocks overwriting and deleting a
	 *                             registered customer's stored cart, so a caller must opt in
	 *                             to it explicitly rather than receive it by omission.
	 *
	 * @since 4.0
	 * @since 6.9.1 Records the customer's key as an identity for the current request,
	 *              tagged with its provenance. Recording happens even when cookies are
	 *              not permitted: it is in-request server-side state, not a cookie, so
	 *              cookie consent governs only what is written to the browser. A
	 *              registered customer resolved only from a typed email is recorded as
	 *              a captured-email identity and never receives a signed tracking
	 *              cookie nor a stored-language update: the signature means "the
	 *              server vouches for this identity", and a typed email is a claim
	 *              anyone can make. Guests resolved from a typed email keep the
	 *              previous behaviour.
	 * @since 6.9.1 The `$provenance` default is PROVENANCE_CAPTURED_EMAIL, so a
	 *              two-argument call gets capture-only rights (fail closed). An
	 *              external caller that genuinely resolved the customer from a
	 *              login or the account's own order must pass PROVENANCE_VERIFIED
	 *              explicitly to keep its previous verified treatment.
	 * @since 6.9.3 PROVENANCE_CAPTURE_ONLY records a customer for the current request
	 *              only, exactly like a captured-email identity for a registered
	 *              customer: no tracking cookie and no language update.
	 * @since 6.9.3 A captured-email identity for a guest the browser holds no verified
	 *              identity for is remembered in the WC session instead of a tracking
	 *              cookie.
	 */
	public static function set_session_customer( $customer, $language = '', $provenance = self::PROVENANCE_CAPTURED_EMAIL ) {
		if ( ! $customer ) {
			return;
		}

		if ( is_user_logged_in() ) {
			return; // session for logged in user will already be set
		}

		$is_capture_only = self::PROVENANCE_CAPTURE_ONLY === $provenance
			|| ( self::PROVENANCE_CAPTURED_EMAIL === $provenance && $customer->is_registered() );

		// A guest created or owned by this session's typing: remembered in the WC session,
		// never in a cookie. A guest the browser already holds a verified identity for is
		// recorded as verified (its cookie is already in the browser).
		if ( ! $is_capture_only && self::PROVENANCE_CAPTURED_EMAIL === $provenance && ! self::is_customer_identity_verified( $customer ) ) {
			self::$captured_email_tracking_key = (string) $customer->get_key();
			self::$session_owned_tracking_key  = (string) $customer->get_key();

			if ( ! headers_sent() && self::cookies_permitted() ) {
				$customer->update_language( $language ? $language : Language::get_current() );
			}

			self::store_session_tracking_key( $customer->get_key() );
			return;
		}

		// Record provenance even when no cookie can or may be written this request.
		if ( $is_capture_only ) {
			self::$captured_email_tracking_key = (string) $customer->get_key();
		} else {
			self::$verified_tracking_key = (string) $customer->get_key();
		}

		if ( headers_sent() || ! self::cookies_permitted() ) {
			return;
		}

		if ( $is_capture_only ) {
			// A typed email must neither mint a durable signed cookie for a registered
			// account or another guest nor rewrite their stored language: both are
			// durable cross-identity writes from an unverified claim.
			return;
		}

		$customer->update_language( $language ? $language : Language::get_current() );

		if ( self::PROVENANCE_CAPTURED_EMAIL === $provenance ) {
			// A typed email never queues the tracking cookie, not even a refresh of the
			// one the browser already holds: the response must not depend on the email.
			return;
		}

		// The new cookie is now the browser's identity: a stale session key must not outrank it.
		if ( self::get_session_tracking_key() !== (string) $customer->get_key() ) {
			self::clear_session_tracking_key();
		}

		self::clear_tracking_stopped();
		self::set_tracking_key_to_be_set( $customer->get_key() );
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
