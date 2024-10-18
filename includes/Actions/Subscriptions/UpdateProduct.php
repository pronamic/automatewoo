<?php

namespace AutomateWoo\Actions\Subscriptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action to update a chosen product line item to a subscription with a chosen quantity.
 *
 * @since 5.4.0
 */
class UpdateProduct extends \AutomateWoo\Action_Subscription_Edit_Product_Abstract {


	/**
	 * Variable products should not be updateed as a line item to subscriptions, only variations.
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
	 * Do not require the quantity input field.
	 *
	 * @var bool
	 */
	protected $require_quantity_field = false;

	/**
	 * Explain to store admin what this action does via a unique title and description.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Update Product', 'automatewoo' );
		$this->description = __( 'Update an existing product line item on a subscription. Only the data set on the action will be updated. This action can be used for bulk editing subscriptions, like changing price or product name. Please note that any coupons applied in the subscription will be reapplied for all line items.', 'automatewoo' );
	}


	/**
	 * Update a given product as a line item to a given subscription.
	 *
	 * @param \WC_Product      $product Product to update to the subscription.
	 * @param \WC_Subscription $subscription Instance of subscription to update the product to.
	 *
	 * @return bool True if the subscription was edited, false if no change was made.
	 */
	protected function edit_subscription( $product, $subscription ) {
		$updated_items_count = 0;

		foreach ( $subscription->get_items() as $subscription_item ) {
			// Since $product can not be a variable product there's no need to check a product variation's parent ID
			$item_product_id = $subscription_item->get_variation_id() ? $subscription_item->get_variation_id() : $subscription_item->get_product_id();

			if ( $product->get_id() === $item_product_id ) {
				$this->apply_changes_to_order_line_item( $product, $subscription_item );
				++$updated_items_count;
			}
		}

		if ( ! $updated_items_count ) {
			return false;
		}

		// Now we need to refresh the subscription to make sure it has the up-to-date line item then recalculate its totals so taxes etc. are updated
		$subscription = wcs_get_subscription( $subscription->get_id() );
		$this->recalculate_subscription_totals( $subscription );

		return true;
	}

	/**
	 * Apply action changes to a specific subscription line item.
	 *
	 * @param \WC_Product            $product The line item product.
	 * @param \WC_Order_Item_Product $item    Subscription line item.
	 */
	protected function apply_changes_to_order_line_item( \WC_Product $product, \WC_Order_Item_Product $item ) {
		$update_product_args = array();

		if ( $this->get_option( 'line_item_name' ) ) {
			$update_product_args['name'] = $this->get_option( 'line_item_name', true );
		}

		if ( $this->get_option( 'line_item_cost' ) || $this->get_option( 'quantity' ) ) {
			$update_product_args['quantity'] = ( $this->get_option( 'quantity' ) ) ? $this->get_option( 'quantity' ) : $item->get_quantity();

			$total = wc_get_price_excluding_tax(
				$product,
				array(
					'price' => $this->get_option( 'line_item_cost', true ),
					'qty'   => $update_product_args['quantity'],
				)
			);

			$update_product_args['subtotal'] = $total;
			$update_product_args['total']    = $total;
		}

		if ( ! empty( $update_product_args ) ) {
			$item->set_props( $update_product_args );
			$item->save();
		}
	}


	/**
	 * Get the description to display on the quantity field for this action
	 */
	protected function get_quantity_field_description() {
		return __( 'Optionally set a new quantity for the product. Defaults to the current quantity set on the subscription.', 'automatewoo' );
	}


	/**
	 * Get the description to display on the cost field for this action
	 */
	protected function get_cost_field_description() {
		return __( 'Optionally set a custom price to use for the line item\'s cost. Do not include a currency symbol. Total line item cost will be this amount * quantity. Price should be entered the same as it would be on the Edit Product screen - taxes inclusive or exclusive. Defaults to no-change - the current price set on the product line item will remain.', 'automatewoo' );
	}


	/**
	 * Get a message to update to the subscription to record the product being updateed by this action.
	 *
	 * Helpful for tracing the history of this action by viewing the subscription's notes.
	 *
	 * @param \WC_Product $product Product being updateed to the subscription. Required so its name can be updateed to the order note.
	 * @return string
	 */
	protected function get_note( $product ) {
		/* translators: %1$s: workflow title, %2$s: product name, %3$d product ID, %4$d workflow ID */
		return sprintf( __( '%1$s workflow run: updated %2$s on subscription. (Product ID: %3$d; Workflow ID: %4$d)', 'automatewoo' ), $this->workflow->get_title(), $product->get_name(), $product->get_id(), $this->workflow->get_id() );
	}
}
