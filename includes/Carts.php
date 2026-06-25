<?php

namespace AutomateWoo;

use AutomateWoo\Carts\CartRestorer;

/**
 * Carts management class
 *
 * @class Carts
 */
class Carts {

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
	 */
	public static function update_stored_customer_cart( $customer ) {
		if ( ! $customer ) {
			return;
		}

		// If the customer is registered and is logged out, their cart will be emptied
		// At this point we are tracking them via cookie so it doesn't make sense to clear their stored cart
		if ( $customer->is_registered() && ! is_user_logged_in() && WC()->cart->is_empty() ) {
			return;
		}

		$stored_cart = $customer->get_cart();

		if ( $stored_cart ) {
			// mark cart if empty otherwise update it
			if ( WC()->cart->is_empty() ) {
				self::mark_cart_as_emptied( $stored_cart );

				/**
				 * Runs when a stored cart is cleared via the frontend.
				 *
				 * @since 4.9.0
				 */
				do_action( 'automatewoo/stored_cart/deleted_via_frontend', $stored_cart );
			} else {
				$stored_cart->sync();

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

			/**
			 * Runs when a new stored cart is created via the frontend.
			 *
			 * @since 4.9.0
			 */
			do_action( 'automatewoo/stored_cart/created_via_frontend', $stored_cart );
		}

		// If there is a current stored cart, store the ID in session data.
		if ( $stored_cart && $stored_cart->is_current() ) {
			self::update_cart_id_in_wc_session( $stored_cart );
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
	 * Clear cart when transition changes from pending, cancelled or failed
	 *
	 * @param int    $order_id
	 * @param string $old_status
	 * @param string $new_status
	 */
	public static function clear_cart_on_order_status_changed( $order_id, $old_status, $new_status ) {
		$failed_statuses = [ 'pending', 'failed', 'cancelled' ];

		if ( in_array( $old_status, $failed_statuses, true ) && ! in_array( $new_status, $failed_statuses, true ) ) {
			self::clear_cart_by_order( $order_id );
		}
	}


	/**
	 * Clears and carts that match the customer from an order
	 *
	 * @param int $order_id
	 */
	public static function clear_cart_by_order( $order_id ) {
		$order = wc_get_order( Clean::id( $order_id ) );
		if ( ! $order ) {
			return;
		}

		$user_id = $order->get_user_id();
		if ( $user_id ) {
			$cart = Cart_Factory::get_by_user_id( $user_id );
			if ( $cart ) {
				self::mark_cart_as_placed_or_recovered( $cart );
			}
		}

		// clear by email
		$guest = Guest_Factory::get_by_email( Clean::email( $order->get_billing_email() ) );
		if ( $guest ) {
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

		if ( $cart && $cart->is_restorable() ) {
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
	 *
	 * @param \WC_Order $order
	 */
	public static function store_cart_id_in_order_meta( $order ) {
		if ( WC()->session ) {
			$order->update_meta_data( 'automatewoo_cart_id', WC()->session->get( 'automatewoo_cart_id' ) );
		}
	}
}
