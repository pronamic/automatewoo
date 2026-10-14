<?php

namespace AutomateWoo;

use AutomateWoo\Carts\CartRestorer;

/**
 * Carts management class
 *
 * @class Carts
 */
class Carts {

	/**
	 * Customer meta key recording the stored cart that was created by an unverified
	 * capture-only identity (an email resolved to an existing guest, a guest order's
	 * billing email, or a legacy tracking cookie held by a registered customer). The
	 * value is `[ 'cart_id' => int, 'sid' => string ]`: the cart ID and the ID of the
	 * WC session that wrote it. Only that session may update
	 * or empty the cart as a capture-only identity. Every other cart is refused,
	 * including carts that predate this protection and carts marked with an older
	 * scalar value, which has no session and so authorises nobody. The mark is cleared
	 * as soon as the cart is written under a verified identity (logged in, placed order,
	 * or a genuine signed tracking cookie).
	 *
	 * A guest whose identity is only a legacy tracking cookie is exempt from this guard,
	 * as it was before the guard existed, and leaves the mark unchanged.
	 *
	 * @since 6.9.1
	 * @since 6.9.3 The value is an array that includes the authoring WC session ID.
	 * @var string
	 */
	const UNVERIFIED_AUTHORED_CART_META_KEY = 'unverified_authored_cart_id';

	/** @var bool - when true cart has been change */
	public static $is_changed = false;

	/**
	 * True if a cart is currently being restored.
	 *
	 * @var bool
	 */
	private static $is_doing_restore = false;


	/**
	 * Loaded if abandoned cart is enabled
	 */
	public static function init() {
		$self = __CLASS__; /** @var $self Carts (for IDE) */

		add_action( 'woocommerce_cart_emptied', [ $self, 'cart_emptied' ] );

		// Clear customer's cart when order status changes from cancelled, failed or pending
		add_action( 'woocommerce_order_status_changed', [ $self, 'clear_cart_on_order_status_changed' ], 10, 3 );

		// If setting to included pending orders as carts is disabled, clear carts as soon as the order is created
		if ( ! AW()->options()->abandoned_cart_includes_pending_orders ) {
			add_action( 'woocommerce_checkout_order_processed', [ $self, 'clear_cart_on_order_created' ] );
			add_action( 'woocommerce_thankyou', [ $self, 'clear_cart_on_order_created' ] );
		}

		// must happen before WC saves it's session data
		add_action( 'shutdown', [ $self, 'maybe_store_cart' ], 10 );

		// change events
		add_action( 'woocommerce_add_to_cart', [ $self, 'mark_as_changed' ] );
		add_action( 'woocommerce_applied_coupon', [ $self, 'mark_as_changed' ] );
		add_action( 'woocommerce_removed_coupon', [ $self, 'mark_as_changed' ] );
		add_action( 'woocommerce_cart_item_removed', [ $self, 'mark_as_changed' ] );
		add_action( 'woocommerce_cart_item_restored', [ $self, 'mark_as_changed' ] );
		add_action( 'woocommerce_after_cart_item_quantity_update', [ $self, 'mark_as_changed' ] );

		add_action( 'woocommerce_after_calculate_totals', [ $self, 'trigger_update_on_cart_and_checkout_pages' ] );

		add_action( 'wp_login', [ $self, 'mark_as_changed_with_cookie' ], 20 );
		add_action( 'wp', [ $self, 'check_for_cart_update_cookie' ], 99 );

		add_action( 'woocommerce_checkout_create_order', [ $self, 'store_cart_id_in_order_meta' ] );
		// Block checkout goes through the Store API, which never fires
		// woocommerce_checkout_create_order, so record the cart there too.
		add_action( 'woocommerce_store_api_checkout_update_order_meta', [ $self, 'store_cart_id_in_order_meta' ] );
	}


	/**
	 * Mark the cart as changed for the current request.
	 */
	public static function mark_as_changed() {
		static::$is_changed = true;
	}


	/**
	 * Set a cookie so the cart is marked as changed on the next request.
	 */
	public static function mark_as_changed_with_cookie() {
		if ( ! headers_sent() && Session_Tracker::cookies_permitted() ) {
			Cookies::set( 'automatewoo_do_cart_update', 1 );
		}
	}


	/**
	 * Important not to run this in the admin area, may not update cart properly
	 */
	public static function check_for_cart_update_cookie() {
		if ( Cookies::get( 'automatewoo_do_cart_update' ) ) {
			self::mark_as_changed();
			Cookies::clear( 'automatewoo_do_cart_update' );
		}
	}


	/**
	 * Mark the cart as changed when totals are calculated on cart and checkout pages.
	 */
	public static function trigger_update_on_cart_and_checkout_pages() {
		if (
				defined( 'WOOCOMMERCE_CART' )
				|| is_checkout()
				|| did_action( 'woocommerce_before_checkout_form' ) // support for one page checkout plugins
		) {
			self::mark_as_changed();
		}
	}


	/**
	 * @return array
	 */
	public static function get_statuses() {
		return apply_filters(
			'automatewoo/cart/statuses',
			[
				Cart::STATUS_ACTIVE    => __( 'Active', 'automatewoo' ),
				Cart::STATUS_ABANDONED => __( 'Abandoned', 'automatewoo' ),
				Cart::STATUS_EMPTIED   => __( 'Emptied', 'automatewoo' ),
				Cart::STATUS_PLACED    => __( 'Placed', 'automatewoo' ),
				Cart::STATUS_RECOVERED => __( 'Recovered', 'automatewoo' ),
			]
		);
	}

	/**
	 * Logic to determine whether we should save the cart on certain hooks
	 */
	public static function maybe_store_cart() {
		if ( ! self::$is_changed ) {
			return; // cart has not changed
		}
		if ( did_action( 'wp_logout' ) ) {
			return; // don't clear the cart after logout
		}
		if ( is_admin() ) {
			return;
		}

		// session only loaded on front end
		if ( WC()->session ) {
			$last_checkout = WC()->session->get( 'automatewoo_checkout_processed_time' );

			// ensure checkout has not been processed in the last 5 minutes
			// this is a fallback for a rare case when the cart session is not cleared after checkout
			if ( $last_checkout && $last_checkout > ( time() - 5 * MINUTE_IN_SECONDS ) ) {
				return;
			}
		}

		$customer = Session_Tracker::get_session_customer();
		if ( $customer ) {
			self::update_stored_customer_cart( $customer );

			$guest = $customer->get_guest();
			if ( $guest ) {
				$guest->do_check_in();
			}
		}
	}


	/**
	 * Updates the stored cart for a customer.
	 * Will also clear a cart if necessary.
	 *
	 * @param Customer $customer
	 *
	 * @since 6.9.3 The same rules apply to guest customers, not only registered ones.
	 * @since 6.9.1 A customer's stored cart is only fully writable when their
	 *              identity is verified (logged in, resolved server-side this request, or
	 *              a genuine signed tracking cookie). A bare cookie value is not proof of
	 *              identity, so a forged cookie carrying a leaked key can no longer
	 *              overwrite the cart — and neither can the marked cookie a legacy value
	 *              is upgraded to, on any later request. Capture-only identities — an
	 *              email-derived identity (another guest's typed email, a guest order's
	 *              billing email, an add-on promoting an email) or an inherited legacy
	 *              cookie — may still
	 *              create a stored cart when the customer has none
	 *              and may keep writing to the cart they created themselves — content
	 *              syncs and emptied marks alike — but no other cart, including every
	 *              cart that predates this protection (fail closed). Marking a cart
	 *              emptied is included because emptied is terminal for the
	 *              abandoned-cart workflow: an unverified claim allowed to set it
	 *              could silently kill the account holder's workflow, and it never
	 *              reflects the visitor's own action anyway — their WC session cart is
	 *              a different cart from the account's stored one. This keeps
	 *              abandoned-cart capture working for existing logged-out shoppers
	 *              and for guest orders placed with a registered account's email when
	 *              the account has no current stored cart, while stopping either claim
	 *              from touching a cart the real account holder built. The remaining limitation: when
	 *              a current stored cart with verified authorship exists, a
	 *              capture-only session never writes to it at all — updates resume
	 *              only under a verified identity (e.g. logging in). The guarded early returns leave the WC session
	 *              completely untouched: refusing to mutate must include refusing to
	 *              delete or repoint the session's cart (see
	 *              update_cart_id_in_wc_session(), which deletes a differing session
	 *              cart as dedupe — a power no refuse path may hold).
	 */
	public static function update_stored_customer_cart( $customer ) {
		if ( ! $customer ) {
			return;
		}

		// WooCommerce does not load the cart in REST API, cron or non-AJAX admin requests
		// (such as admin-post.php), but third-party code can still reach this method there.
		// With no cart loaded there is nothing to sync, and treating it as an empty cart
		// would wrongly mark the stored cart emptied.
		if ( ! WC()->cart ) {
			return;
		}

		$is_capture_only_identity = false;
		$is_legacy_cookie_guest   = false;

		if ( ! is_user_logged_in() && ! Session_Tracker::is_customer_identity_verified( $customer ) ) {
			$is_captured_email = Session_Tracker::is_customer_identity_from_captured_email( $customer );

			if ( ! $is_captured_email && ! Session_Tracker::is_customer_identity_from_legacy_cookie( $customer ) ) {
				return; // refuse: an unverified identity mutates nothing, the WC session included
			}

			// A guest holding only a legacy cookie keeps the behaviour it had before the
			// authorship guard: carts saved before the guard have no mark to match.
			$is_legacy_cookie_guest   = ! $is_captured_email && ! $customer->is_registered();
			$is_capture_only_identity = ! $is_legacy_cookie_guest;
		}

		// A verified cookie identity's WC session cart is emptied by logging out, so an
		// empty session cart is not evidence they emptied their stored cart — leave it.
		// A capture-only identity is different: logging in replaces its cookie with a
		// signed one, so it never holds a logout-emptied session; let it fall through
		// so it can mark the cart it authored itself emptied. The emptied branch below
		// enforces that authorship, exactly like the content branch.
		if ( $customer->is_registered() && ! is_user_logged_in() && WC()->cart->is_empty() && ! $is_capture_only_identity ) {
			return;
		}

		$stored_cart = $customer->get_cart();

		if ( $stored_cart ) {
			// mark cart if empty otherwise update it
			if ( WC()->cart->is_empty() ) {
				if ( $is_capture_only_identity && ! self::is_cart_unverified_authored( $customer, $stored_cart ) ) {
					// Same rule as the content branch: a capture-only identity may only
					// write to the cart it authored itself, and marking a cart emptied
					// IS a write — emptied is terminal, so an unverified claim (a typed
					// email in an open comment form is enough) allowed to set it could
					// kill the account holder's abandoned-cart workflow at will. Nor
					// does the empty session reflect anything this visitor did to the
					// stored cart: their WC session cart is a different cart. Genuine
					// guest checkout is unaffected — a shopper at checkout has items
					// (the sync branch), and a cart captured from their email
					// carries the authorship mark, so they can still empty it later.
					return;
				}
				self::mark_cart_as_emptied( $stored_cart );
				self::record_cart_authorship( $customer, $stored_cart, $is_capture_only_identity, $is_legacy_cookie_guest );

				/**
				 * Runs when a stored cart is cleared via the frontend.
				 *
				 * @since 4.9.0
				 */
				do_action( 'automatewoo/stored_cart/deleted_via_frontend', $stored_cart );
			} else {
				if ( $is_capture_only_identity && ! self::is_cart_unverified_authored( $customer, $stored_cart ) ) {
					return; // a capture-only identity may only sync content into the cart it created itself, never any other
				}

				$stored_cart->sync();
				self::record_cart_authorship( $customer, $stored_cart, $is_capture_only_identity, $is_legacy_cookie_guest );

				/**
				 * Runs when stored cart is updated via the frontend.
				 *
				 * @since 4.9.0
				 */
				do_action( 'automatewoo/stored_cart/updated_via_frontend', $stored_cart );
			}
			// create a new cart if the current session cart isn't empty
		} elseif ( ! WC()->cart->is_empty() ) {
			$stored_cart = new Cart();
			if ( $customer->is_registered() ) {
				$stored_cart->set_user_id( $customer->get_user_id() );
			} else {
				$stored_cart->set_guest_id( $customer->get_guest_id() );
			}
			$stored_cart->set_token();
			$stored_cart->sync();
			self::record_cart_authorship( $customer, $stored_cart, $is_capture_only_identity, $is_legacy_cookie_guest );

			/**
			 * Runs when a new stored cart is created via the frontend.
			 *
			 * @since 4.9.0
			 */
			do_action( 'automatewoo/stored_cart/created_via_frontend', $stored_cart );
		}

		// If there is a current stored cart, store the ID in session data.
		// This runs only after a write pass the identity was allowed to make: the
		// guarded early returns above skip it, because update_cart_id_in_wc_session()
		// deletes a differing session cart as dedupe, and a refuse path must not be
		// able to destroy anything. The cost is that a guarded request can leave the
		// session's automatewoo_cart_id stale (or never written) for
		// store_cart_id_in_order_meta(). That is safe because clear_cart_by_order()
		// only trusts the recorded id when it resolves to a real cart, and falls
		// back to customer resolution otherwise.
		if ( $stored_cart && $stored_cart->is_current() ) {
			self::update_cart_id_in_wc_session( $stored_cart );
		}
	}

	/**
	 * Whether a customer's stored cart carries the unverified authorship
	 * mark, i.e. it was created by a capture-only identity (email-derived or inherited
	 * legacy cookie) and has not been written under a verified identity since.
	 *
	 * @since 6.9.1
	 *
	 * @param Customer $customer
	 * @param Cart     $cart
	 *
	 * @return bool
	 */
	private static function is_cart_unverified_authored( $customer, $cart ) {
		$mark = self::get_authorship_mark( $customer );

		return $mark['cart_id'] && $mark['cart_id'] === (int) $cart->get_id() && self::is_current_wc_session( $mark['sid'] );
	}

	/**
	 * Reads the authorship mark. A scalar value written by an earlier version has no
	 * session, so it never matches the current one.
	 *
	 * @since 6.9.3
	 *
	 * @param Customer $customer
	 *
	 * @return array{cart_id: int, sid: string}
	 */
	private static function get_authorship_mark( $customer ) {
		$value = $customer->get_meta( self::UNVERIFIED_AUTHORED_CART_META_KEY );

		if ( is_array( $value ) ) {
			return [
				'cart_id' => (int) ( $value['cart_id'] ?? 0 ),
				'sid'     => (string) ( $value['sid'] ?? '' ),
			];
		}

		return [
			'cart_id' => (int) $value,
			'sid'     => '',
		];
	}

	/**
	 * @since 6.9.3
	 *
	 * @param string $sid
	 *
	 * @return bool
	 */
	private static function is_current_wc_session( $sid ) {
		return '' !== $sid && WC()->session && (string) WC()->session->get_customer_id() === $sid;
	}

	/**
	 * Records how a customer's stored cart was just written. A capture-only
	 * write (email-derived or inherited legacy cookie) marks the cart as
	 * unverified-authored, together with the WC session that wrote it, so only that
	 * session can keep updating it across requests; a verified write clears the mark,
	 * permanently protecting the cart from capture-only writes. Carts never marked —
	 * including all carts that predate this protection — are protected by default.
	 *
	 * @since 6.9.1
	 * @since 6.9.3 The mark carries the authoring WC session id. A write by a legacy-cookie
	 *              guest leaves the mark as it is.
	 *
	 * @param Customer $customer
	 * @param Cart     $cart
	 * @param bool     $is_capture_only_identity
	 * @param bool     $is_legacy_cookie_guest
	 */
	private static function record_cart_authorship( $customer, $cart, $is_capture_only_identity, $is_legacy_cookie_guest = false ) {
		if ( ! $cart->get_id() || $is_legacy_cookie_guest ) {
			return;
		}

		if ( $is_capture_only_identity ) {
			$customer->update_meta(
				self::UNVERIFIED_AUTHORED_CART_META_KEY,
				[
					'cart_id' => (int) $cart->get_id(),
					'sid'     => WC()->session ? (string) WC()->session->get_customer_id() : '',
				]
			);
		} elseif ( self::get_authorship_mark( $customer )['cart_id'] ) {
			$customer->delete_meta( self::UNVERIFIED_AUTHORED_CART_META_KEY );
		}
	}

	/**
	 * Stores our cart ID in the WC customer session.
	 *
	 * Also clears the previous cart when a new cart is created for the same customer.
	 * This logic isn't actually relied on but provides an extra way to protect against duplicate carts.
	 *
	 * @since 4.9.0
	 *
	 * @param Cart $cart
	 */
	public static function update_cart_id_in_wc_session( $cart ) {
		if ( ! WC()->session ) {
			return;
		}

		$wc_session_cart_id = (int) WC()->session->get( 'automatewoo_cart_id' );

		// If the cart set in the session is different from the current one, that cart is obsolete and should be deleted
		if ( $wc_session_cart_id && $wc_session_cart_id !== $cart->get_id() ) {
			$wc_session_cart = Cart_Factory::get( $wc_session_cart_id );
			if ( $wc_session_cart && $wc_session_cart->is_current() ) {
				$wc_session_cart->delete();
			}
		}

		WC()->session->set( 'automatewoo_cart_id', $cart->get_id() );
	}

	/**
	 * Woocommerce_cart_emptied fires when an order is placed and the cart is emptied.
	 * It does NOT fire when a user empties their cart.
	 * It appears to also NOT fire when an a pending or failed order is generated,
	 * important that it remains this way for the abandoned_cart_includes_pending_orders option
	 */
	public static function cart_emptied() {
		if ( did_action( 'wp_logout' ) ) {
			return; // don't clear cart after logout
		}

		// Ensure carts are cleared for users and guests registered at checkout
		$user_id = Session_Tracker::get_detected_user_id();
		$guest   = Session_Tracker::get_current_guest();

		if ( $user_id ) {
			$cart = Cart_Factory::get_by_user_id( $user_id );
			if ( $cart ) {
				self::mark_cart_as_placed_or_recovered( $cart );
			}
		}

		if ( $guest ) {
			self::mark_cart_as_placed_or_recovered( $guest->get_cart() );
		}

		self::$is_changed = false; // cart is up-to-date
	}


	/**
	 * Ensure the stored abandoned cart is removed when an order is created.
	 * Clears even if payment has not gone through.
	 *
	 * @param int $order_id
	 */
	public static function clear_cart_on_order_created( $order_id ) {

		if ( WC()->session ) {
			WC()->session->set( 'automatewoo_checkout_processed_time', time() );
		}

		// clear by session key
		$guest = Session_Tracker::get_current_guest();
		if ( $guest ) {
			self::mark_cart_as_placed_or_recovered( $guest->get_cart() );
		}

		self::clear_cart_by_order( $order_id );
	}


	/**
	 * Clear cart when transition changes from pending, cancelled or failed,
	 * or when the order becomes paid.
	 *
	 * @since 6.9.1 Also runs when the order transitions into a paid status from a
	 *              non-paid one (e.g. on-hold to processing for a bank transfer).
	 *              The old guard only fired when leaving pending/failed/cancelled,
	 *              so the paid-order cleanup in clear_cart_by_order() never re-ran
	 *              for orders that passed through on-hold. Running twice for one
	 *              order (pending to processing satisfies both conditions across
	 *              hooks) is harmless: marking a cart placed/recovered is idempotent.
	 *
	 * @param int    $order_id
	 * @param string $old_status
	 * @param string $new_status
	 */
	public static function clear_cart_on_order_status_changed( $order_id, $old_status, $new_status ) {
		$failed_statuses = [ 'pending', 'failed', 'cancelled' ];
		$paid_statuses   = wc_get_is_paid_statuses();

		$leaves_failed_status = in_array( $old_status, $failed_statuses, true ) && ! in_array( $new_status, $failed_statuses, true );
		$becomes_paid         = ! in_array( $old_status, $paid_statuses, true ) && in_array( $new_status, $paid_statuses, true );

		if ( $leaves_failed_status || $becomes_paid ) {
			self::clear_cart_by_order( $order_id );
		}
	}


	/**
	 * Marks the carts an order settles.
	 *
	 * The rule, in one sentence: an order's cleanup marks the current cart of
	 * every identity the order itself attests — the cart recorded on it at
	 * checkout, the guest under its billing email, and (for a genuinely paid,
	 * non-zero guest order) the registered account under that email — skipping a
	 * lookup only when the recorded cart already belongs to that identity, and
	 * guessing at the buyer's current cart only when no recorded cart resolves.
	 * The lookups are peers, not alternatives: each reads its own evidence off
	 * the order, so which carts get marked never depends on which branch
	 * happened to run first.
	 *
	 * Why each lookup exists, and what bounds it:
	 *
	 * - The recorded cart (`automatewoo_cart_id`, written at checkout) is the
	 *   one cart the order proves it was placed from. A record of 0, or one
	 *   pointing at a cart that no longer exists, is NOT trusted: 0 can mean "no
	 *   stored cart existed", but equally that the session pointer was missing
	 *   or stale when the order was created (expired session row, or a guarded
	 *   early return skipped the pointer write) while a stored cart very much
	 *   exists — so anything short of a resolvable id falls back to resolving
	 *   the order's own user, exactly like an order that predates the record.
	 *   When the record DOES resolve, that fallback must not run: it guesses at
	 *   whatever cart the buyer holds now, and marked the wrong cart whenever
	 *   the two diverged (a fresh cart abandoned while a bank-transfer order sat
	 *   on hold).
	 *
	 * - The billing-email lookups are not guesses about the same thing: they
	 *   read the order's own billing email, so they run on every path — a
	 *   resolvable record proves which cart the order came from, not that the
	 *   email's owner holds no other converted cart. A session pointer that was
	 *   never refreshed (a capture-only identity takes guarded early returns in
	 *   update_stored_customer_cart()) can record a resolvable-but-wrong cart,
	 *   and treating the record as licence to skip the email lookups left the
	 *   account's real cart live and kept emailing the shopper about the order
	 *   they had just paid for. The one exception per lookup: when the recorded
	 *   cart itself belongs to that guest or that account, the order has already
	 *   named which of their carts converted, and a newer cart they abandoned
	 *   while the order sat unpaid must stay live.
	 *
	 * - The registered-account lookup additionally requires a paid, non-zero
	 *   guest order. Guest checkout permits typing a registered address, so the
	 *   buyer may own no guest record and never mint a session identity — but a
	 *   typed email alone must not touch a registered account's data: an unpaid
	 *   order is a claim (the paid transition re-runs this method later), and
	 *   is_paid() is status-based, so a zero-total order (100%-off coupon, free
	 *   product) reaches processing for nothing. The total is read in the `edit`
	 *   context: the `view` value passes through the `woocommerce_order_get_total`
	 *   filter, and a gate deciding whether a cart may be marked must read what
	 *   the store recorded, not what a theme displays. The accepted trade-offs:
	 *   a registered shopper guest-checking-out a fully-discounted order with no
	 *   resolvable cart record keeps their abandoned cart live, and an attacker
	 *   willing to place a real paid or cash-on-delivery order — spending money,
	 *   or creating a traceable order the merchant sees and cancels — can still
	 *   reach the account's cart; that residual is accepted because it attaches
	 *   cost and visibility, where the closed vector was free and silent. This
	 *   never verifies the browser or writes a cookie: it only marks carts.
	 *
	 * @since 6.9.1 Restructured around the rule above: the recorded cart replaced
	 *              the customer-resolution guess, and the billing-email lookups
	 *              (guest, and registered account for a paid non-zero guest order)
	 *              became peers that run on every path.
	 *
	 * @param int $order_id
	 */
	public static function clear_cart_by_order( $order_id ) {
		$order = wc_get_order( Clean::id( $order_id ) );
		if ( ! $order ) {
			return;
		}

		$recorded_cart_id = (int) $order->get_meta( 'automatewoo_cart_id' );
		$recorded_cart    = $recorded_cart_id ? Cart_Factory::get( $recorded_cart_id ) : false;
		$billing_email    = Clean::email( $order->get_billing_email() );
		$order_user_id    = $order->get_user_id();

		// The cart the order was placed from. Only when no recorded cart resolves
		// may the cleanup guess via the order's own user: marking whatever cart
		// that user holds now would suppress a genuine abandoned-cart workflow, or
		// count an unrelated cart as recovered.
		if ( $recorded_cart ) {
			self::mark_cart_as_placed_or_recovered( $recorded_cart );
		} elseif ( $order_user_id ) {
			$cart = Cart_Factory::get_by_user_id( $order_user_id );
			if ( $cart ) {
				self::mark_cart_as_placed_or_recovered( $cart );
			}
		}

		// The registered account under the order's billing email, when a guest
		// order genuinely paid a non-zero total (see the docblock for why both
		// gates are required and why the total is read in the edit context).
		// Skipped only when the recorded cart already belongs to that account.
		// Never creates a customer if none exists.
		if ( ! $order_user_id && $order->is_paid() && (float) $order->get_total( 'edit' ) > 0 ) {
			$customer = Customer_Factory::get_by_email( $billing_email, false );

			if (
				$customer && $customer->is_registered()
				&& ( ! $recorded_cart || (int) $recorded_cart->get_user_id() !== (int) $customer->get_user_id() )
			) {
				$cart = Cart_Factory::get_by_user_id( $customer->get_user_id() );
				if ( $cart ) {
					self::mark_cart_as_placed_or_recovered( $cart );
				}
			}
		}

		// The guest under the order's billing email — an earlier, unfinished
		// guest checkout by the same shopper converted with this purchase too.
		// Skipped only when the recorded cart already belongs to that guest.
		$guest = Guest_Factory::get_by_email( $billing_email );
		if ( $guest && ( ! $recorded_cart || (int) $recorded_cart->get_guest_id() !== (int) $guest->get_id() ) ) {
			self::mark_cart_as_placed_or_recovered( $guest->get_cart() );
		}

		self::$is_changed = false; // cart is up-to-date
	}


	/**
	 * Restores a cart into the current session.
	 *
	 * @param Cart|bool $cart
	 *
	 * @return bool True if the cart was restored, false on failure.
	 */
	public static function restore_cart( $cart ) {
		$was_restored = false;

		// WooCommerce does not load the cart and session in REST API, cron or non-AJAX
		// admin requests.
		if ( $cart && $cart->is_restorable() && WC()->cart && WC()->session ) {
			self::$is_doing_restore = true;

			$cart_restorer = new CartRestorer( $cart, WC()->cart, WC()->session );
			$was_restored  = $cart_restorer->restore();

			self::$is_doing_restore = false;
		}

		return $was_restored;
	}


	/**
	 * @param Cart|bool $cart
	 */
	private static function mark_cart_as_emptied( $cart ) {
		if ( $cart instanceof Cart ) {
			$cart->update_status( Cart::STATUS_EMPTIED );
		}
	}


	/**
	 * @param Cart|bool $cart
	 */
	private static function mark_cart_as_placed_or_recovered( $cart ) {
		if ( ! $cart instanceof Cart ) {
			return;
		}

		$status = $cart->has_been_abandoned() ? Cart::STATUS_RECOVERED : Cart::STATUS_PLACED;
		$cart->update_status( $status );
	}


	/**
	 * Is a cart restore in progress?
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	public static function is_doing_restore() {
		return self::$is_doing_restore;
	}


	/**
	 * Delete old inactive carts
	 */
	public static function clean_stored_carts() {
		global $wpdb;

		$clear_inactive_carts_after = absint( AW()->options()->clear_inactive_carts_after );
		if ( ! $clear_inactive_carts_after ) {
			return;
		}

		$clear_inactive_carts_after = -1 * $clear_inactive_carts_after;
		$delay_date                 = new DateTime();
		$delay_date->modify( "{$clear_inactive_carts_after} days" );

		$table = Database_Tables::get( 'carts' );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from internal $table->get_name(); not user input and cannot be parameterized.
		$wpdb->query(
			$wpdb->prepare(
				"
			DELETE FROM {$table->get_name()}
			WHERE last_modified < %s",
				$delay_date->to_mysql_string()
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * When a checkout order is created store the cart ID in order meta.
	 *
	 * @since 4.9.0
	 * @since 6.9.1 Also runs for Store API (block) checkout, and always stores an
	 *              integer — 0 when the session holds no cart pointer. A 0 is a
	 *              debugging breadcrumb only: the pointer can be legitimately
	 *              missing while a stored cart exists (expired session row, or a
	 *              guarded write pass skipped the pointer update), so
	 *              clear_cart_by_order() treats 0 like no record at all and falls
	 *              back to customer resolution.
	 *
	 * @param \WC_Order $order
	 */
	public static function store_cart_id_in_order_meta( $order ) {
		if ( WC()->session ) {
			$order->update_meta_data( 'automatewoo_cart_id', (int) WC()->session->get( 'automatewoo_cart_id' ) );
		}
	}
}
