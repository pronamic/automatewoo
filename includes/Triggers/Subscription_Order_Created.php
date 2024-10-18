<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Subscription_Workflow_Helper;
use AutomateWoo\Trigger_Order_Created;
use AutomateWoo\Workflow;

defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription_Order_Created.
 *
 * This trigger works like the Order Created trigger but includes a subscription data type and only triggers for orders created via a subscription.
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class Subscription_Order_Created extends Trigger_Order_Created {

	/**
	 * Subscription_Order_Created constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->supplied_data_items[] = 'subscription';
	}

	/**
	 * Load admin props.
	 */
	public function load_admin_details() {
		$this->title        = __( 'Subscription Order Created', 'automatewoo' );
		$this->description  = __( 'This trigger fires after any type of subscription order is created. The order may not yet be paid.', 'automatewoo' );
		$this->description .= ' ' . Subscription_Workflow_Helper::get_subscription_order_trigger_description();
		$this->group        = Subscription_Workflow_Helper::get_group_name();
	}

	/**
	 * Load fields.
	 */
	public function load_fields() {
		$this->add_field( Subscription_Workflow_Helper::get_subscription_order_types_field() );
	}

	/**
	 * Trigger for subscription order.
	 *
	 * @param \WC_Order|int $order
	 */
	public function trigger_for_order( $order ) {
		Subscription_Workflow_Helper::trigger_for_subscription_order( $this, $order );
	}

	/**
	 * Validate workflow.
	 *
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		if ( ! Subscription_Workflow_Helper::validate_subscription_order_types_field( $workflow ) ) {
			return false;
		}

		return parent::validate_workflow( $workflow );
	}
}
