<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

aw_deprecated_class( Trigger_Abstract_Subscriptions::class, '5.2.0', Subscription_Workflow_Helper::class );

/**
 * @deprecated Use the Subscription_Workflow_Helper class instead.
 *
 * @class Trigger_Abstract_Subscriptions
 * @since 2.1
 */
abstract class Trigger_Abstract_Subscriptions extends Trigger {

	/** @var bool - trigger can run per subscription or per line item */
	public $is_run_for_each_line_item = false;


	function __construct() {
		if ( $this->is_run_for_each_line_item ) {
			$this->supplied_data_items = [ 'customer', 'subscription', 'product', 'subscription_item' ];
		}
		else {
			$this->supplied_data_items = [ 'customer', 'subscription' ];
		}

		parent::__construct();
	}


	function load_admin_details() {
		$this->group = Subscription_Workflow_Helper::get_group_name();
	}

	/**
	 * @param int|\WC_Subscription $subscription
	 */
	function trigger_for_subscription( $subscription ) {
		Subscription_Workflow_Helper::trigger_for_subscription( $this, $subscription );
	}

	/**
	 * @param int|\WC_Subscription $subscription
	 */
	function trigger_for_each_subscription_line_item( $subscription ) {
		Subscription_Workflow_Helper::trigger_for_each_subscription_line_item( $this, $subscription );
	}

	function add_field_subscription_products() {
		$this->add_field( Subscription_Workflow_Helper::get_products_field() );
	}

	function add_field_active_only() {
		$this->add_field( Subscription_Workflow_Helper::get_active_subscriptions_only_field() );
	}

	/**
	 * @param $workflow Workflow
	 * @return bool
	 */
	protected function validate_subscription_products_field( $workflow ) {
		return Subscription_Workflow_Helper::validate_products_field( $workflow );
	}

	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	protected function validate_subscription_active_only_field( $workflow ) {
		return Subscription_Workflow_Helper::validate_active_subscriptions_only_field( $workflow );
	}

	/**
	 * @param $subscription
	 * @return \WC_Subscription|false
	 */
	function get_subscription( $subscription ) {
		return wcs_get_subscription( $subscription );
	}

}
