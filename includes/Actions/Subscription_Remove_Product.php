<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action to remove all line items matching a chosen products ID from a workflow's subscription.
 *
 * @class Action_Subscription_Remove_Product
 * @since 4.4
 */
class Action_Subscription_Remove_Product extends Action_Subscription_Edit_Product_Abstract {


	/**
	 * Overload parent::$requires_quantity_field to prevent the quantity field being added by
	 * parent::load_fields(), as it is not used for product removal.
	 *
	 * @var bool
	 */
	protected $load_quantity_field = false;


	/**
	 * Explain to store admin what this action does via a unique title and description.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Remove Product', 'automatewoo' );
		$this->description = __( 'Remove a product line item or items from a subscription, if any line items match the chosen product. This is useful for bulk editing subscriptions, or to change the products provided to a subscriber at different stages of their subscription\'s lifecycle. Please note: all line items that match the chosen product will be removed. Choosing a variable product will also remove any variations of that product. Choose a variation to remove only specific variations. Also note that any coupons applied in the subscription will be reapplied for all remaining line items.', 'automatewoo' );
	}


	/**
	 * Remove all line items for a product that have an ID matching a given product.
	 *
	 * Variations need to be removed by variation ID. They can not be removed by passing
	 * the parent variable product's ID.
	 *
	 * More than one line item may be removed if more than one line item matches the given
	 * product's ID.
	 *
	 * @param \WC_Product      $product Product to removed from the subscription.
	 * @param \WC_Subscription $subscription Instance of subscription to remove the product from.
	 *
	 * @return bool True if the subscription was edited, false if no change was made.
	 */
	protected function edit_subscription( $product, $subscription ) {
		$edited = false;

		foreach ( $subscription->get_items() as $item ) {
			// This will be the variation_id if the product is a variation.
			$product_id        = $product->get_id();
			$item_product_id   = $item->get_product_id();
			$item_variation_id = $item->get_variation_id();
			if ( $product_id === $item_product_id || $product_id === $item_variation_id ) {
				$edited = true;
				$subscription->remove_item( $item->get_id() );
			}
		}

		// updates totals and saves subscription
		if ( $edited ) {
			$this->recalculate_subscription_totals( $subscription );
		}

		return $edited;
	}


	/**
	 * Create a note recording the product name and workflow name to add after removing products.
	 *
	 * @param \WC_Product $product Product being removed from the subscription. Required so its name can be added to the order note.
	 * @return string
	 */
	protected function get_note( $product ) {
		/* translators: %1$s: workflow title, %2$s: product name, %3$d product ID, %4$d workflow ID */
		return sprintf( __( '%1$s workflow run: removed all line items for %2$s. (Product ID: %3$d; Workflow ID: %4$d)', 'automatewoo' ), $this->workflow->get_title(), $product->get_name(), $product->get_id(), $this->workflow->get_id() );
	}
}
