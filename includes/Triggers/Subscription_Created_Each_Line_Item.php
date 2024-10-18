<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Subscription_Workflow_Helper;
use AutomateWoo\Trigger_Subscription_Created;

defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription_Created_Each_Line_Item.
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class Subscription_Created_Each_Line_Item extends Trigger_Subscription_Created {

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
		$this->title       = __( 'Subscription Created - Each Line Item', 'automatewoo' );
		$this->description = __( 'This trigger fires after a subscription is created which happens before payment is confirmed. Using this trigger allows access to the product data of each subscription line item.', 'automatewoo' );
		$this->group       = Subscription_Workflow_Helper::get_group_name();
	}

	/**
	 * Load fields.
	 */
	public function load_fields() {
		// Intentionally avoid loading extra fields.
	}

	/**
	 * Handle subscription created event.
	 *
	 * @param int $subscription_id
	 */
	public function handle_subscription_created( $subscription_id ) {
		Subscription_Workflow_Helper::trigger_for_each_subscription_line_item( $this, $subscription_id );
	}
}
