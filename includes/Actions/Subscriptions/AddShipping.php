<?php

namespace AutomateWoo\Actions\Subscriptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action to add a chosen shipping line item to a subscription with a chosen cost.
 *
 * @since 5.4.0
 */
class AddShipping extends AbstractEditShipping {


	/**
	 * Overload parent::$requires_quantity_field to prevent the quantity field being added by
	 * parent::load_fields(), as it is not used for shipping removal.
	 *
	 * @var bool
	 */
	protected $load_quantity_field = false;


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
		$this->title       = __( 'Add Shipping', 'automatewoo' );
		$this->description = __( 'Add shipping as a new line item on a subscription. This action can be used to change the shipping and other line items charged to a subscriber at different stages of their subscription\'s lifecycle.', 'automatewoo' );
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

		$rate = new \WC_Shipping_Rate( $shipping_data['shipping_method_id'], $shipping_data['line_item_name'], $shipping_data['line_item_cost'], [], $shipping_data['shipping_method_id'] );
		$item = new \WC_Order_Item_Shipping();
		$item->set_props(
			[
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			]
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$subscription->add_item( $item );
		$subscription->save();
		$subscription->calculate_totals();

		return true;
	}


	/**
	 * Get a message to add to the subscription to record the shipping being added by this action.
	 *
	 * Helpful for tracing the history of this action by viewing the subscription's notes.
	 *
	 * @param array $shipping_data Shipping line item data. Same data as the return value of @see $this->get_object_for_edit().
	 * @return string
	 */
	protected function get_note( $shipping_data ) {
		/* translators: %1$s: workflow title, %2$s line item name, %3$d shipping method ID, %4$d workflow ID */
		return sprintf( __( '%1$s workflow run: added %2$s to subscription. (Shipping Method ID: %3$d; Workflow ID: %4$d)', 'automatewoo' ), $this->workflow->get_title(), $shipping_data['line_item_name'], $shipping_data['shipping_method_id'], $this->workflow->get_id() );
	}
}
