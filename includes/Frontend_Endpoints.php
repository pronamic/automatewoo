<?php

namespace AutomateWoo;

use AutomateWoo\Frontend_Endpoints\Login_Redirect;

/**
 * @class Frontend_Endpoints
 * @since 2.8.6
 */
class Frontend_Endpoints {


	/**
	 * Dispatch the current frontend endpoint action.
	 *
	 * @return void
	 */
	public static function handle() {
		$action = sanitize_key( aw_request( 'aw-action' ) );

		if ( ! $action ) {
			return;
		}

		aw_no_page_cache();

		switch ( $action ) {

			case 'restore-cart':
				self::restore_cart();
				break;

			case 'unsubscribe':
				self::catch_legacy_unsubscribe_url();
				break;

			case 'click':
				Tracking::handle_click_tracking_url();
				break;

			case 'open':
				Tracking::handle_open_tracking_url();
				break;

			case 'reorder':
				self::reorder();
				break;

			case 'login-redirect':
				( new Login_Redirect() )->handle_endpoint();
				break;

		}
	}


	/**
	 * Redirect legacy unsubscribe links to the communication page
	 */
	public static function catch_legacy_unsubscribe_url() {
		$customer_key = Clean::string( aw_request( 'customer_key' ) );
		$email        = Clean::email( aw_request( 'user' ) ); // user param is legacy, todo remove later
		$customer     = false;

		if ( $customer_key ) {
			$customer = Customer_Factory::get_by_key( $customer_key );
		} elseif ( $email ) {
			$customer = Customer_Factory::get_by_email( $email );
		}

		$redirect = Frontend::get_communication_page_permalink( $customer, 'unsubscribe', Clean::id( aw_request( 'workflow' ) ) );

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
	}


	/**
	 * Restore a saved cart from its token and redirect the customer.
	 *
	 * @return false|void False when no token is present, otherwise redirects and exits.
	 */
	public static function restore_cart() {
		$token    = Clean::string( aw_request( 'token' ) );
		$redirect = Clean::string( aw_request( 'redirect' ) );

		if ( ! $token ) {
			return false;
		}

		$cart             = Cart_Factory::get_by_token( $token );
		$restored         = Carts::restore_cart( $cart );
		$url_params       = [];
		$redirect_options = [ 'cart', 'checkout' ];

		if ( ! in_array( $redirect, $redirect_options, true ) ) {
			$redirect = 'cart';
		}

		if ( $restored ) {
			wc_add_notice( __( 'Your cart has been restored.', 'automatewoo' ) );
			$url_params['aw-cart-restored'] = 'success';
		} elseif ( $cart && ! $cart->is_current() ) {
			// The cart still exists but is in a terminal state (emptied, placed
			// or recovered) — it was already purchased or cleared, so this is not
			// an expiry/failure and should not show the error notice.
			wc_add_notice( __( 'This cart has already been restored or is no longer available.', 'automatewoo' ), 'notice' );
			$url_params['aw-cart-restored'] = 'unavailable';
		} elseif ( $cart && ! $cart->has_items() ) {
			// The cart is still current but has no items, so there is nothing to
			// restore. It has not expired, so don't show the failure notice.
			wc_add_notice( __( 'This cart is empty, so there is nothing to restore.', 'automatewoo' ), 'notice' );
			$url_params['aw-cart-restored'] = 'empty';
		} else {
			wc_add_notice( __( 'Your cart could not be restored, it may have expired.', 'automatewoo' ), 'notice' );
			$url_params['aw-cart-restored'] = 'fail';
		}

		self::redirect_while_preserving_url_args(
			add_query_arg( $url_params, wc_get_page_permalink( $redirect ) ),
			[ 'token', 'redirect' ]
		);
	}


	/**
	 * @see \Automattic\WooCommerce\Internal\Orders\OrderActionsRestController and
	 * @see \WC_Cart_Session::populate_cart_from_order() — the modern WC order-again
	 *      flow lives there now (WC_Form_Handler::order_again() is a deprecated stub).
	 */
	public static function reorder() {

		$order_id = wc_get_order_id_by_order_key( Clean::string( aw_request( 'aw-order-key' ) ) );
		$order    = wc_get_order( absint( $order_id ) );

		if ( ! $order ) {
			wc_add_notice( __( 'The previous order could not be found.', 'automatewoo' ) );
			return;
		}

		WC()->cart->empty_cart();

		// Build the cart array directly, mirroring WC_Cart_Session::populate_cart_from_order().
		// This deliberately does NOT call WC()->cart->add_to_cart(): doing so would fire the
		// `woocommerce_add_to_cart` action and trigger extensions (e.g. Chained Products) that
		// auto-add child products to the cart — duplicating items that are already part of the
		// order being replayed. See #1010.
		$cart_contents = [];
		$order_items   = $order->get_items();

		foreach ( $order_items as $item ) {
			$product_id     = (int) apply_filters( 'woocommerce_add_to_cart_product_id', $item->get_product_id() );
			$quantity       = $item->get_quantity();
			$variation_id   = (int) $item->get_variation_id();
			$variations     = [];
			$cart_item_data = apply_filters( 'woocommerce_order_again_cart_item_data', [], $item, $order );
			$product        = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			// Prevent reordering variable products if no selected variation.
			if ( ! $variation_id && $product->is_type( 'variable' ) ) {
				continue;
			}

			// Prevent reordering items specifically out of stock.
			if ( ! $product->is_in_stock() ) {
				continue;
			}

			foreach ( $item->get_meta_data() as $meta ) {
				if ( taxonomy_is_product_attribute( $meta->key ) ) {
					$term                     = get_term_by( 'slug', $meta->value, $meta->key );
					$variations[ $meta->key ] = $term ? $term->name : $meta->value;
				} elseif ( meta_is_product_attribute( $meta->key, $meta->value, $product_id ) ) {
					$variations[ $meta->key ] = $meta->value;
				}
			}

			if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations, $cart_item_data ) ) {
				continue;
			}

			// Resolve to variation if applicable so cart-level price / data is correct.
			$cart_id      = WC()->cart->generate_cart_id( $product_id, $variation_id, $variations, $cart_item_data );
			$product_data = wc_get_product( $variation_id ? $variation_id : $product_id );

			// `$product_id` may have been mutated by the `woocommerce_add_to_cart_product_id`
			// filter above and no longer resolve, in which case we can't build a valid cart
			// item — skip rather than pass `false` into `wc_get_cart_item_data_hash()`.
			if ( ! $product_data ) {
				continue;
			}

			$cart_contents[ $cart_id ] = apply_filters(
				'woocommerce_add_order_again_cart_item',
				array_merge(
					$cart_item_data,
					[
						'key'          => $cart_id,
						'product_id'   => $product_id,
						'variation_id' => $variation_id,
						'variation'    => $variations,
						'quantity'     => $quantity,
						'data'         => $product_data,
						'data_hash'    => wc_get_cart_item_data_hash( $product_data ),
					]
				),
				$cart_id
			);
		}

		// Fire before the cart is committed (matching WC core) so listeners can
		// mutate $cart_contents by reference and have it reflected in the cart.
		do_action_ref_array( 'woocommerce_ordered_again', [ $order->get_id(), $order_items, &$cart_contents ] );

		WC()->cart->set_cart_contents( $cart_contents );
		WC()->cart->set_session();

		$num_items_in_cart           = count( $cart_contents );
		$num_items_in_original_order = count( $order_items );

		if ( $num_items_in_original_order > $num_items_in_cart ) {
			wc_add_notice(
				sprintf(
					/* translators: %d Number of unavailable items from previous order. */
					_n(
						'%d item from your previous order is currently unavailable and could not be added to your cart.',
						'%d items from your previous order are currently unavailable and could not be added to your cart.',
						$num_items_in_original_order - $num_items_in_cart,
						'automatewoo'
					),
					$num_items_in_original_order - $num_items_in_cart
				),
				'error'
			);
		}

		if ( $num_items_in_cart > 0 ) {
			wc_add_notice( __( 'The cart has been filled with the items from your previous order.', 'automatewoo' ) );
		}

		// Redirect to cart
		self::redirect_while_preserving_url_args( wc_get_cart_url(), [ 'aw-order-key' ] );
	}

	/**
	 * Redirect to a new URL while preserving the current URL args.
	 *
	 * Preserves args such as 'utm_source' or 'apply_coupon'.
	 *
	 * @since 4.8.0
	 *
	 * @param string $url
	 * @param array  $args_to_remove Specify args that should not be preserved.
	 */
	public static function redirect_while_preserving_url_args( $url, $args_to_remove = [] ) {
		// Always remove current action arg
		$args_to_remove[] = 'aw-action';

		// Gets and sanitize current URL params
		$args = aw_get_query_args( $args_to_remove );

		wp_safe_redirect( add_query_arg( $args, $url ) );
		exit;
	}
}
