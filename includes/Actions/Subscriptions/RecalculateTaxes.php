<?php

namespace AutomateWoo\Actions\Subscriptions;

use AutomateWoo\Action;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action to recalculate all taxes for a subscription.
 *
 * @since 5.4.0
 */
class RecalculateTaxes extends Action {

	/**
	 * A subscription is needed to run this action.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'subscription' ];

	/**
	 * Explain to store admin what this action does via a unique title and description.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Recalculate Taxes', 'automatewoo' );
		$this->description = __(
			'Recalculate all taxes on a subscription based on the store\'s current tax settings. This is useful for bulk editing subscriptions when new tax rates are introduced. Tax rates are based on the subscription billing or shipping address (as set on WooCommerce > Settings > Tax > Calculate tax based on).',
			'automatewoo'
		);
		$this->group       = __( 'Subscription', 'automatewoo' );
	}

	/**
	 * Run the action.
	 */
	public function run() {
		$subscription = $this->workflow->data_layer()->get_subscription();
		if ( ! $subscription ) {
			return;
		}

		$subscription->calculate_totals( true );
		$subscription->add_order_note(
			sprintf(
				/* translators: %1$s workflow title, %2$d workflow ID */
				__( '%1$s workflow run: recalculated taxes. (Workflow ID: %2$d)', 'automatewoo' ),
				$this->workflow->get_title(),
				$this->workflow->get_id()
			),
			false,
			false
		);
	}
}
