<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Frontend_Endpoints\Login_Redirect;

/**
 * @class Frontend_Endpoints
 * @since 2.8.6
 */
class Frontend_Endpoints {


	static function handle() {
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
				( new Login_Redirect )->handle_endpoint();
				break;

		}
	}


	/**
	 * Redirect legacy unsubscribe links to the communication page
	 */
	static function catch_legacy_unsubscribe_url() {
		$customer_key = Clean::string( aw_request( 'customer_key' ) );
		$email = Clean::email( aw_request( 'user' ) ); // user param is legacy, todo remove later
		$customer = false;

		if ( $customer_key ) {
			$customer = Customer_Factory::get_by_key( $customer_key );
		}
		elseif ( $email ) {
			$customer = Customer_Factory::get_by_email( $email );
		}

		$redirect = Frontend::get_communication_page_permalink( $customer, 'unsubscribe' );

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
	}


	static function restore_cart() {
		$token = Clean::string( aw_request( 'token' ) );
		$redirect = Clean::string( aw_request( 'redirect' ) );

		if ( ! $token ) {
			return false;
		}

		$restored         = Carts::restore_cart( Cart_Factory::get_by_token( $token ) );
		$url_params       = [];
		$redirect_options = [ 'cart', 'checkout' ];

		if ( ! in_array( $redirect, $redirect_options, true ) ) {
			$redirect = 'cart';
		}

		if ( $restored ) {
			wc_add_notice( __( 'Your cart has been restored.', 'automatewoo' ) );
			$url_params['aw-cart-restored'] = 'success';
		}
		else {
			wc_add_notice( __( 'Your cart could not be restored, it may have expired.', 'automatewoo' ), 'notice' );
			$url_params['aw-cart-restored'] = 'fail';
		}

		self::redirect_while_preserving_url_args(
			add_query_arg( $url_params, wc_get_page_permalink( $redirect ) ),
			[ 'token', 'redirect' ]
		);
	}


	/**
	 * @see \WC_Form_Handler::order_again()
	 */
	static function reorder() {

		$order_id = wc_get_order_id_by_order_key( Clean::string( aw_request( 'aw-order-key' ) ) );
		$order = wc_get_order( absint( $order_id ) );

		if ( ! $order ) {
			wc_add_notice( __( 'The previous order could not be found.', 'automatewoo' ) );
			return;
		}

		WC()->cart->empty_cart();

		// Copy products from the order to the cart
		$order_items = $order->get_items();
		foreach ( $order_items as $item ) {
			// Load all product info including variation data
			$product_id   = (int) apply_filters( 'woocommerce_add_to_cart_product_id', $item->get_product_id() );
			$quantity     = $item->get_quantity();
			$variation_id = $item->get_variation_id();
			$variations   = array();
			$cart_item_data = apply_filters( 'woocommerce_order_again_cart_item_data', array(), $item, $order );

			foreach ( $item->get_meta_data() as $meta ) {
				if ( taxonomy_is_product_attribute( $meta->key ) ) {
					$term = get_term_by( 'slug', $meta->value, $meta->key );
					$variations[ $meta->key ] = $term ? $term->name : $meta->value;
				} elseif ( meta_is_product_attribute( $meta->key, $meta->value, $product_id ) ) {
					$variations[ $meta->key ] = $meta->value;
				}
			}

			// Prevent reordering variable products if no selected variation.
			if ( ! $variation_id && ( $product = $item->get_product() ) && $product->is_type( 'variable' ) ) {
				continue;
			}

			// Add to cart validation
			if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations, $cart_item_data ) ) {
				continue;
			}

			WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations, $cart_item_data );
		}

		do_action( 'woocommerce_ordered_again', $order->get_id() );

		$num_items_in_cart = count( WC()->cart->get_cart() );
		$num_items_in_original_order = count( $order_items );

		if ( $num_items_in_original_order > $num_items_in_cart ) {
			wc_add_notice(
				sprintf( _n(
					'%d item from your previous order is currently unavailable and could not be added to your cart.',
					'%d items from your previous order are currently unavailable and could not be added to your cart.',
					$num_items_in_original_order - $num_items_in_cart,
					'automatewoo'
				), $num_items_in_original_order - $num_items_in_cart ),
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
