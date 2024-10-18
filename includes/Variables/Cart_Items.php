<?php

namespace AutomateWoo;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Variable {{ cart.items }} class.
 *
 * @class Variable_Cart_Items
 */
class Variable_Cart_Items extends Variable_Abstract_Product_Display {

	/**
	 * Declare cart table support.
	 *
	 * @var bool
	 */
	public $supports_cart_table = true;

	/**
	 * Load admin details.
	 *
	 * @return void
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Display a product listing of the items in the cart.', 'automatewoo' );
	}

	/**
	 * Loop through items in the cart and fetch WC_Product objects.
	 *
	 * @param Cart     $cart       The customer's Cart object.
	 * @param array    $parameters Parameters for this variable.
	 * @param Workflow $workflow   The current Workflow.
	 *
	 * @throws Exception If cart doesn't contain any valid products.
	 *
	 * @return mixed
	 */
	public function get_value( $cart, $parameters, $workflow ) {
		$cart_items = $cart->get_items();
		$template   = isset( $parameters['template'] ) ? $parameters['template'] : false;

		$products    = array();
		$product_ids = array();

		if ( is_array( $cart_items ) ) {
			foreach ( $cart_items as $item ) {
				$id = ( 0 !== $item->get_variation_id() ) ? $item->get_variation_id() : $item->get_product_id();
				if ( $id ) {
					$product_ids[] = $id;
				}
			}

			$product_ids = array_unique( $product_ids );

			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );

				// Ensure only published products are included.
				if ( is_a( $product, 'WC_Product' ) && $product->get_status() === 'publish' ) {
					$products[] = $product;
				}
			}
		}

		if ( empty( $products ) ) {
			// Return an empty string if fallback is set so it can be displayed otherwise throw an exception.
			if ( isset( $parameters['fallback'] ) ) {
				return '';
			} else {
				throw new Exception( esc_html__( '{{ cart.items }} returned no products so the workflow was aborted', 'automatewoo' ) );
			}
		}

		$args = array_merge(
			$this->get_default_product_template_args( $workflow, $parameters ),
			array(
				'products'   => $products,
				'cart_items' => $cart->get_items_raw(), // legacy.
				'cart'       => $cart,
			)
		);

		return $this->get_product_display_html( $template, $args );
	}
}
