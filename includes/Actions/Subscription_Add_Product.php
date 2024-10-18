<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action to add a chosen product line item to a subscription with a chosen quantity.
 *
 * @class Action_Subscription_Add_Product
 * @since 4.4
 */
class Action_Subscription_Add_Product extends Action_Subscription_Edit_Product_Abstract {


	/**
	 * Variable products should not be added as a line item to subscriptions, only variations.
	 *
	 * @var bool
	 */
	protected $allow_variable_products = false;


	/**
	 * Flag to define whether the instance of this action requires a name text input field.
	 *
	 * @var bool
	 */
	protected $load_name_field = true;


	/**
	 * Flag to define whether the instance of this action requires a price input field to
	 * be displayed on the action's admin UI.
	 *
	 * @var bool
	 */
	protected $load_cost_field = true;


	/**
	 * Explain to store admin what this action does via a unique title and description.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Add Product', 'automatewoo' );
		$this->description = __( 'Add a product as a new line item on a subscription. The item will be added using the price set on the product. This action can be used for bulk editing subscriptions, or to change the products provided to a subscriber at different stages of their subscription\'s lifecycle. Please note that any coupons applied in the subscription will be reapplied for all line items.', 'automatewoo' );
	}


	/**
	 * Add a given product as a line item to a given subscription.
	 *
	 * @param \WC_Product      $product Product to add to the subscription.
	 * @param \WC_Subscription $subscription Instance of subscription to add the product to.
	 *
	 * @return bool True if the subscription was edited, false if no change was made.
	 */
	protected function edit_subscription( $product, $subscription ) {

		$add_product_args = array();

		if ( $this->get_option( 'line_item_name' ) ) {
			$add_product_args['name'] = $this->get_option( 'line_item_name', true );
		}

		if ( $this->get_option( 'line_item_cost' ) ) {
			$add_product_args['subtotal'] = wc_get_price_excluding_tax(
				$product,
				array(
					'price' => $this->get_option( 'line_item_cost', true ),
					'qty'   => $this->get_option( 'quantity' ),
				)
			);
			$add_product_args['total']    = $add_product_args['subtotal'];
		}

		$subscription->add_product( $product, $this->get_option( 'quantity' ), $add_product_args );
		$this->recalculate_subscription_totals( $subscription );

		return true;
	}


	/**
	 * Get a message to add to the subscription to record the product being added by this action.
	 *
	 * Helpful for tracing the history of this action by viewing the subscription's notes.
	 *
	 * @param \WC_Product $product Product being added to the subscription. Required so its name can be added to the order note.
	 * @return string
	 */
	protected function get_note( $product ) {
		/* translators: %1$s: workflow title, %2$s: product name, %3$d product ID, %4$d workflow ID */
		return sprintf( __( '%1$s workflow run: added %2$s to subscription. (Product ID: %3$d; Workflow ID: %4$d)', 'automatewoo' ), $this->workflow->get_title(), $product->get_name(), $product->get_id(), $this->workflow->get_id() );
	}
}
