<?php

namespace AutomateWoo\Carts;

use AutomateWoo\Cart;
use AutomateWoo\Logger;
use WC_Cart;
use WC_Session;

/**
 * Class CartRestorer
 *
 * Restores a saved cart to the current session.
 *
 * @since 5.4.0
 */
class CartRestorer {

	/**
	 * @var Cart
	 */
	protected $stored_cart;

	/**
	 * @var WC_Cart
	 */
	protected $current_cart;

	/**
	 * @var WC_Session
	 */
	protected $current_session;

	/**
	 * The key of the cart item that is currently being restored.
	 *
	 * @var string
	 */
	protected $current_cart_item_key;

	/**
	 * Unique key/identifier of the cart item that is currently being restored.
	 *
	 * @var string
	 */
	protected $current_unfiltered_cart_item_key;

	/**
	 * CartRestorer constructor.
	 *
	 * @param Cart       $stored_cart The stored cart to be restored.
	 * @param WC_Cart    $current_cart
	 * @param WC_Session $current_session
	 */
	public function __construct( Cart $stored_cart, WC_Cart $current_cart, WC_Session $current_session ) {
		$this->stored_cart     = $stored_cart;
		$this->current_cart    = $current_cart;
		$this->current_session = $current_session;
	}

	/**
	 * Restore the stored cart items and coupons to the current session cart.
	 *
	 * @return bool True if the cart was restored, false on failure.
	 */
	public function restore(): bool {
		if ( ! $this->stored_cart->has_items() ) {
			// Nothing to restore
			return false;
		}

		$notices_backup = wc_get_notices();

		$this->restore_items();
		$this->restore_coupons();

		// Revert notices to backup to remove all notices that were added when re-adding coupons and products to cart
		$this->current_session->set( 'wc_notices', $notices_backup );

		do_action( 'automatewoo/cart/restored', $this->stored_cart );

		return true;
	}

	/**
	 * Restore cart items.
	 */
	protected function restore_items() {
		$existing_items = $this->current_cart->get_cart_for_session();

		foreach ( $this->stored_cart->get_items() as $item ) {
			if ( isset( $existing_items[ $item->get_key() ] ) ) {
				continue; // item already exists in cart
			}

			// Use a filter to force the new cart item key to match the key of the stored cart item
			// This is important to prevent duplicate cart items upon login or if multiple cart restores occur
			$this->current_cart_item_key = $item->get_key();

			// Calculate a unique identifier for this cart item that we can check to ensure that the callback
			// hooked to 'woocommerce_cart_id' filter will modify the cart id of this item,
			// and not other items potentially added to the cart via hooks fired in 'WC_Cart::add_to_cart'.
			$this->current_unfiltered_cart_item_key = WC()->cart->generate_cart_id( $item->get_product_id(), $item->get_variation_id(), $item->get_variation_data(), $item->get_data() );

			add_filter( 'woocommerce_cart_id', [ $this, 'filter_cart_key_to_force_new_key_to_match_stored_key' ], 10, 5 );

			// \WC_Cart::add_to_cart wrongly contains a @throws tag
			try {
				$this->current_cart->add_to_cart(
					$item->get_product_id(),
					$item->get_quantity(),
					$item->get_variation_id(),
					$item->get_variation_data(),
					$item->get_data()
				);
			} catch ( \Exception $e ) {
				Logger::error( 'cart-restore', $e->getMessage() );
			}

			remove_filter( 'woocommerce_cart_id', [ $this, 'filter_cart_key_to_force_new_key_to_match_stored_key' ], 10, 5 );
		}
	}

	/**
	 * Restore stored cart coupons to current cart.
	 */
	protected function restore_coupons() {
		foreach ( $this->stored_cart->get_coupons() as $coupon_code => $coupon_data ) {
			if ( ! $this->current_cart->has_discount( $coupon_code ) ) {
				$this->current_cart->add_discount( $coupon_code );
			}
		}
	}

	/**
	 * Filter the cart item key to preserve the key of the item that was just restored.
	 *
	 * @param  string $cart_id
	 * @param  int    $product_id      contains the id of the product to add to the cart.
	 * @param  int    $variation_id    ID of the variation being added to the cart.
	 * @param  array  $variation       attribute values.
	 * @param  array  $cart_item_data  extra cart item data we want to pass into the item.
	 *
	 * @return string
	 */
	public function filter_cart_key_to_force_new_key_to_match_stored_key( string $cart_id, $product_id, $variation_id, $variation, $cart_item_data ): string {

		if ( $this->current_cart_item_key ) {

			// Calculate a unique identifier for this cart item
			// and compare it against the one stored in the 'current_unfiltered_cart_item_key' property.
			// If it's the same, we know that we are filtering the cart item id we intended to.
			remove_filter( 'woocommerce_cart_id', [ $this, 'filter_cart_key_to_force_new_key_to_match_stored_key' ] );
			$unfiltered_cart_id = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );
			add_filter( 'woocommerce_cart_id', [ $this, 'filter_cart_key_to_force_new_key_to_match_stored_key' ], 10, 5 );

			if ( $this->current_unfiltered_cart_item_key === $unfiltered_cart_id ) {
				return $this->current_cart_item_key;
			}
		}

		return $cart_id;
	}
}
