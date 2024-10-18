<?php

namespace AutomateWoo\Actions\Subscriptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action to update a shipping method from a subscription.
 *
 * @since 5.4.0
 */
class UpdateShipping extends AddShipping {


	/**
	 * Explain to store admin what this action does via a unique title and description.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Update Shipping', 'automatewoo' );
		$this->description = __( 'Update a shipping line item or items from a subscription, if any line items match the chosen shipping method. This is useful for bulk editing subscriptions, or to change the shipping charged to a subscriber at different stages of their subscription\'s lifecycle. Please note: all line items for the chosen shipping method will be updated.', 'automatewoo' );
	}


	/**
	 * Add a given shipping as a line item to a given subscription.
	 *
	 * @param array            $shipping_data Shipping line item data. Same data as the return value of @see $this->get_object_for_edit().
	 * @param \WC_Subscription $subscription Instance of subscription to add the shipping to.
	 *
	 * @return bool True if the subscription was edited, false if no change was made.
	 */
	protected function edit_subscription( $shipping_data, $subscription ) {

		$shipping_line_item = null;

		foreach ( $subscription->get_shipping_methods() as $line_item ) {
			// Same approach used in Abstract_WC_Order::has_shipping_method() to check for method
			if ( 0 === strpos( $line_item->get_method_id(), $shipping_data['shipping_method_id'] ) ) {
				$shipping_line_item = $line_item;
				break;
			}
		}

		// No item for that shipping method on this subscription
		if ( empty( $shipping_line_item ) ) {
			return false;
		}

		$update_args = [];

		if ( $this->get_option( 'line_item_name' ) ) {
			$update_args['name'] = $this->get_option( 'line_item_name', true );
		}

		if ( $this->get_option( 'line_item_cost' ) ) {
			$update_args['total'] = $this->get_option( 'line_item_cost', true );
		}

		if ( ! empty( $update_args ) ) {
			$shipping_line_item->set_props( $update_args );
			$shipping_line_item->save();
		}

		// Now we need to refresh the subscription to make sure it has the up-to-date line item then recalculate its totals so taxes etc. are updated
		$subscription = wcs_get_subscription( $subscription->get_id() );
		$subscription->calculate_totals();

		return true;
	}


	/**
	 * Create a note recording the shipping method ID and workflow name to add after updating shipping.
	 *
	 * Helpful for tracing the history of this action by viewing the subscription's notes.
	 *
	 * @param array $shipping_data Shipping line item data. Same data as the return value of @see $this->get_object_for_edit().
	 * @return string
	 */
	protected function get_note( $shipping_data ) {
		/* translators: %1$s: workflow title, %2$d shipping method ID, %3$d workflow ID */
		return sprintf( __( '%1$s workflow run: updated shipping on subscription. (Shipping Method ID: %2$d; Workflow ID: %3$d)', 'automatewoo' ), $this->workflow->get_title(), $shipping_data['shipping_method_id'], $this->workflow->get_id() );
	}
}
