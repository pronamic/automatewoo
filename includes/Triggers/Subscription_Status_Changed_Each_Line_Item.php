<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Subscription_Status_Changed_Each_Line_Item
 *
 * @since 2.9
 * @package AutomateWoo
 */
class Trigger_Subscription_Status_Changed_Each_Line_Item extends Trigger_Subscription_Status_Changed {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ 'subscription', 'customer', 'product', 'subscription_item' ];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Subscription Status Changed - Each Line Item', 'automatewoo' );
		$this->description = __( 'This trigger runs for every line item of a subscription when the status changes. Using this trigger allows access to the product data of the subscription line item.', 'automatewoo' );
		$this->group       = Subscription_Workflow_Helper::get_group_name();
	}

	/**
	 * Handle status changed.
	 *
	 * @param int    $subscription_id
	 * @param string $new_status
	 * @param string $old_status
	 */
	public function handle_status_changed( $subscription_id, $new_status, $old_status ) {
		Temporary_Data::set( 'subscription_old_status', $subscription_id, $old_status );
		Temporary_Data::set( 'subscription_new_status', $subscription_id, $new_status );
		Subscription_Workflow_Helper::trigger_for_each_subscription_line_item( $this, $subscription_id );
	}
}
