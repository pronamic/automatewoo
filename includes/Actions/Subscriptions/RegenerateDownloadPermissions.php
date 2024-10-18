<?php

namespace AutomateWoo\Actions\Subscriptions;

use AutomateWoo\Action;
use Exception;
use WC_Data_Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action to regenerate download permissions for a subscription.
 *
 * @since 5.4.0
 */
class RegenerateDownloadPermissions extends Action {

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
		$this->title = __( 'Regenerate Download Permissions', 'automatewoo' );
		$this->group = __( 'Subscription', 'automatewoo' );
	}

	/**
	 * Run the action.
	 *
	 * @throws Exception If there's an error running the action.
	 */
	public function run() {
		$subscription = $this->workflow->data_layer()->get_subscription();
		if ( ! $subscription ) {
			return;
		}

		// Method used in \WC_Meta_Box_Order_Actions::save
		$data_store = WC_Data_Store::load( 'customer-download' );
		$data_store->delete_by_order_id( $subscription->get_id() );
		wc_downloadable_product_permissions( $subscription->get_id(), true );
	}
}
