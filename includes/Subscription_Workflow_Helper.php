<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription_Workflow_Helper
 *
 * @since 4.5
 *
 * @package AutomateWoo
 */
class Subscription_Workflow_Helper {

	/**
	 * Get the subscription group name.
	 *
	 * @return string
	 */
	public static function get_group_name() {
		return __( 'Subscriptions', 'automatewoo' );
	}

	/**
	 * Get the subscription products field.
	 *
	 * @return Fields\Product
	 */
	public static function get_products_field() {
		$field = new Fields\Product();
		$field->set_name( 'subscription_products' );
		$field->set_title( __( 'Subscription products', 'automatewoo' ) );
		$field->set_description( __( 'Select products here to make this workflow only run on subscriptions with matching products. Leave blank to run for all products.', 'automatewoo' ) );
		$field->set_multiple( true );
		$field->set_allow_variations( true );

		return $field;
	}

	/**
	 * Get the 'active subscriptions only' field.
	 *
	 * @return Fields\Checkbox
	 */
	public static function get_active_subscriptions_only_field() {
		$field = new Fields\Checkbox();
		$field->set_name( 'active_only' );
		$field->set_title( __( 'Active subscriptions only', 'automatewoo' ) );
		$field->set_description( __( 'Enable to ensure the subscription is still active when the workflow runs. This is useful if the workflow is not run immediately.', 'automatewoo' ) );
		$field->default_to_checked = true;

		return $field;
	}

	/**
	 * Validate the subscription products field for a workflow.
	 *
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public static function validate_products_field( $workflow ) {
		$subscription          = $workflow->data_layer()->get_subscription();
		$subscription_products = $workflow->get_trigger_option( 'subscription_products' );

		// there's no product restriction
		if ( empty( $subscription_products ) ) {
			return true;
		}

		$included_product_ids = [];

		foreach ( $subscription->get_items() as $item ) {
			$included_product_ids[] = $item->get_product_id();
			$included_product_ids[] = $item->get_variation_id();
		}

		return (bool) array_intersect( $included_product_ids, $subscription_products );
	}


	/**
	 * Validate the 'active subscriptions only' field.
	 *
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public static function validate_active_subscriptions_only_field( $workflow ) {
		$subscription = $workflow->data_layer()->get_subscription();

		if ( $workflow->get_trigger_option( 'active_only' ) ) {
			if ( ! $subscription->has_status( 'active' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Trigger for subscription.
	 *
	 * @param Trigger              $trigger
	 * @param int|\WC_Subscription $subscription
	 */
	public static function trigger_for_subscription( $trigger, $subscription ) {
		$subscription = wcs_get_subscription( $subscription );

		if ( ! $subscription ) {
			return;
		}

		$trigger->maybe_run(
			[
				'subscription' => $subscription,
				'customer'     => Customer_Factory::get_by_user_id( $subscription->get_user_id() ),
			]
		);
	}

	/**
	 * Trigger for each line item in a subscription.
	 *
	 * @param Trigger              $trigger
	 * @param int|\WC_Subscription $subscription
	 */
	public static function trigger_for_each_subscription_line_item( $trigger, $subscription ) {
		$subscription = wcs_get_subscription( $subscription );

		if ( ! $subscription ) {
			return;
		}

		$customer = Customer_Factory::get_by_user_id( $subscription->get_user_id() );

		foreach ( $subscription->get_items() as $order_item_id => $order_item ) {
			$trigger->maybe_run(
				[
					'subscription'      => $subscription,
					'customer'          => $customer,
					'product'           => $order_item->get_product(),
					'subscription_item' => $order_item,
				]
			);
		}
	}

	/**
	 * Trigger for an order only if it's a subscription order.
	 *
	 * @since 4.8.0
	 *
	 * @param Trigger       $trigger
	 * @param \WC_Order|int $order
	 */
	public static function trigger_for_subscription_order( $trigger, $order ) {
		$order = wc_get_order( $order );
		if ( ! $order ) {
			return;
		}

		$subscription = self::get_subscription_for_order( $order );
		if ( ! $subscription ) {
			return;
		}

		$trigger->maybe_run(
			[
				'order'        => $order,
				'customer'     => Customer_Factory::get_by_order( $order ),
				'subscription' => $subscription,
			]
		);
	}

	/**
	 * Get subscription statuses.
	 *
	 * Excludes the 'wc-switched' status.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function get_subscription_statuses() {
		$statuses = wcs_get_subscription_statuses();
		unset( $statuses['wc-switched'] );
		return $statuses;
	}

	/**
	 * Get the order's first related subscription.
	 *
	 * Orders can technically have multiple subscriptions this method returns only the one.
	 *
	 * @param \WC_Order    $order
	 * @param string|array $order_type
	 *
	 * @return \WC_Subscription|false
	 *
	 * @since 4.8.0
	 */
	protected static function get_subscription_for_order( $order, $order_type = 'any' ) {
		if ( ! wcs_order_contains_subscription( $order, $order_type ) ) {
			return false;
		}

		return current( wcs_get_subscriptions_for_order( $order, [ 'order_type' => $order_type ] ) );
	}

	/**
	 * Get description for all subscription order triggers.
	 *
	 * @return string
	 *
	 * @since 4.8.0
	 */
	public static function get_subscription_order_trigger_description() {
		return __( 'If the order has multiple subscriptions, only the first subscription will be used in the workflow. Subscription parent orders can have multiple subscriptions if the order contains products with different billing periods.', 'automatewoo' );
	}

	/**
	 * Get list of subscription order types.
	 *
	 * @return array
	 *
	 * @since 4.8.0
	 */
	public static function get_subscription_order_types() {
		return [
			'parent'      => __( 'Parent', 'automatewoo' ),
			'renewal'     => __( 'Renewal', 'automatewoo' ),
			'resubscribe' => __( 'Resubscribe', 'automatewoo' ),
			'switch'      => __( 'Switch', 'automatewoo' ),
		];
	}

	/**
	 * Get subscription order types field.
	 *
	 * @return Fields\Select
	 *
	 * @since 4.8.0
	 */
	public static function get_subscription_order_types_field() {
		$field = new Fields\Select();
		$field->set_name( 'order_types' );
		$field->set_title( __( 'Subscription order types', 'automatewoo' ) );
		$field->set_options( self::get_subscription_order_types() );
		$field->set_placeholder( __( '[Any]', 'automatewoo' ) );
		$field->set_multiple( true );
		$field->set_description( __( 'Select which subscription order types this workflow will run for.', 'automatewoo' ) );
		return $field;
	}

	/**
	 * Validate the subscription order types field for a workflow.
	 *
	 * @param Workflow $workflow
	 *
	 * @return bool
	 *
	 * @since 4.8.0
	 */
	public static function validate_subscription_order_types_field( $workflow ) {
		$valid_order_types = $workflow->get_trigger_option( 'order_types' );
		$order             = $workflow->data_layer()->get_order();

		if ( ! $order ) {
			return false;
		}

		if ( ! $valid_order_types ) {
			$valid_order_types = 'any';
		}

		return wcs_order_contains_subscription( $order, $valid_order_types );
	}

	/**
	 * Check if an order is a Subscription
	 *
	 * @param int $order_id The order ID
	 * @return bool True if the order is a subscription
	 * @since 5.6.7
	 */
	public static function is_subscription( int $order_id ) {
		if ( Integrations::is_subscriptions_active() && wcs_is_subscription( $order_id ) ) {
			return true;
		}

		return false;
	}
}
