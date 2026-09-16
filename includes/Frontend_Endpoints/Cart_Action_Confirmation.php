<?php

namespace AutomateWoo\Frontend_Endpoints;

use AutomateWoo\Cart_Factory;
use AutomateWoo\Clean;

/**
 * Confirmation gate and page for the reorder and restore-cart endpoints.
 *
 * The reorder and restore-cart links live in emails and are followed as plain
 * page navigations, so the cart is changed only once the visitor confirms it
 * with a POST that carries a matching nonce. The dispatcher checks
 * {@see self::is_confirmed()} and runs the mutation on a confirmed POST;
 * otherwise it calls {@see self::show()} to render this page.
 *
 * @since x.x.x
 */
class Cart_Action_Confirmation {

	/**
	 * Whether the current request is a valid confirmation submission for a cart action.
	 *
	 * @param string $action The endpoint action, 'reorder' or 'restore-cart'.
	 * @return bool
	 */
	public static function is_confirmed( $action ) {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';

		if ( 'POST' !== $method ) {
			return false;
		}

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		return (bool) wp_verify_nonce( $nonce, self::nonce_name( $action ) );
	}

	/**
	 * Schedule the confirmation page to render for an unconfirmed cart action.
	 *
	 * @return void
	 */
	public static function show() {
		// The theme and main query are not ready this early (wp_loaded), so defer
		// the confirmation page to template_redirect.
		add_action( 'template_redirect', [ __CLASS__, 'render' ] );
	}

	/**
	 * Render the confirmation page as the cart page's own content.
	 *
	 * Rendered on `template_redirect` once the query is resolved, so it inherits
	 * the store's header, footer and layout and works whether the cart page uses
	 * the block or the shortcode. AutomateWoo's reorder / restore-cart links
	 * already target the cart page; the redirect only covers a link placed on
	 * another page.
	 *
	 * @return void
	 */
	public static function render() {
		$action = sanitize_key( aw_request( 'aw-action' ) );

		if ( ! in_array( $action, [ 'reorder', 'restore-cart' ], true ) ) {
			return;
		}

		$cart_url = wc_get_cart_url();

		if ( ! is_cart() && $cart_url ) {
			wp_safe_redirect( add_query_arg( aw_get_query_args(), $cart_url ) );
			exit;
		}

		// Run late so the returned markup is not reprocessed by wpautop.
		add_filter( 'the_content', [ __CLASS__, 'get_content' ], 99 );
	}

	/**
	 * Replace the cart page content with the cart-action confirmation form.
	 *
	 * @param string $content The post content being rendered.
	 * @return string
	 */
	public static function get_content( $content ) {
		if ( ! is_main_query() || ! in_the_loop() || ! is_cart() ) {
			return $content;
		}

		remove_filter( 'the_content', [ __CLASS__, 'get_content' ], 99 );

		$action = sanitize_key( aw_request( 'aw-action' ) );

		if ( 'reorder' === $action ) {
			$order        = wc_get_order( wc_get_order_id_by_order_key( Clean::string( aw_request( 'aw-order-key' ) ) ) );
			$is_available = $order && count( $order->get_items() ) > 0;
			$heading      = __( 'Reorder items from your previous order?', 'automatewoo' );
			$description  = __( 'Your current cart will be replaced with the items from your previous order.', 'automatewoo' );
			$button_label = __( 'Reorder', 'automatewoo' );
		} else {
			$cart         = Cart_Factory::get_by_token( Clean::string( aw_request( 'token' ) ) );
			$is_available = $cart && $cart->is_restorable();
			$heading      = __( 'Restore your saved cart?', 'automatewoo' );
			$description  = __( 'The items from your saved cart will be added to your cart.', 'automatewoo' );
			$button_label = __( 'Restore cart', 'automatewoo' );
		}

		ob_start();

		aw_get_template(
			'cart-action-confirmation.php',
			[
				'is_available' => $is_available,
				'heading'      => $heading,
				'description'  => $description,
				'button_label' => $button_label,
				'nonce_name'   => self::nonce_name( $action ),
				'cart_url'     => wc_get_cart_url(),
			]
		);

		return ob_get_clean();
	}

	/**
	 * Nonce action name for a confirmable cart action.
	 *
	 * @param string $action The endpoint action, 'reorder' or 'restore-cart'.
	 * @return string
	 */
	private static function nonce_name( $action ) {
		return "automatewoo-{$action}";
	}
}
