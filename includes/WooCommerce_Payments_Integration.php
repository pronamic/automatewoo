<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class WooCommerce_Payments_Integration
 * @since 5.5.12
 */
class WooCommerce_Payments_Integration {

	/**
	 * WooCommerce_Payments_Integration constructor.
	 */
	public function __construct() {
		add_filter( 'automatewoo/actions', array( $this, 'maybe_remove_incompatible_actions' ) );
	}

	/**
	 * Removes AutomateWoo actions that are incompatible with WooCommerce Payments.
	 *
	 * @param  array $actions AutomateWoo actions.
	 * @return array          Potentially filtered AutomateWoo actions.
	 */
	public function maybe_remove_incompatible_actions( $actions ) {
		// Subscriptions not enabled, bail.
		if ( ! Integrations::is_subscriptions_active() ) {
			return $actions;
		}

		// The WooCommerce Subscriptions extension is active, bail.
		if ( class_exists( '\WC_Subscriptions' ) ) {
			return $actions;
		}

		$payment_gateways = WC()->payment_gateways->payment_gateways();

		// The WooCommerce Payments payment gateway is not enabled, bail.
		if (
			! isset( $payment_gateways['woocommerce_payments'] ) ||
			! isset( $payment_gateways['woocommerce_payments']->enabled ) ||
			'yes' !== $payment_gateways['woocommerce_payments']->enabled
		) {
			return $actions;
		}

		$wcpay_gateway = $payment_gateways['woocommerce_payments'];

		$actions_to_remove = [];

		if ( ! $wcpay_gateway->supports( 'subscription_amount_changes' ) ) {
			$actions_to_remove = array_merge(
				$actions_to_remove,
				array(
					'subscription_add_coupon',
					'subscription_add_product',
					'subscription_add_shipping',
					'subscription_recalculate_taxes',
					'subscription_remove_coupon',
					'subscription_remove_product',
					'subscription_remove_shipping',
					'subscription_update_currency',
					'subscription_update_product',
					'subscription_update_shipping',
				)
			);
		}

		if ( ! $wcpay_gateway->supports( 'subscription_date_changes' ) ) {
			$actions_to_remove = array_merge(
				$actions_to_remove,
				array(
					'subscription_update_next_payment_date',
					'subscription_update_schedule',
				)
			);
		}

		foreach ( $actions_to_remove as $action_to_remove ) {
			unset( $actions[ $action_to_remove ] );
		}

		return $actions;
	}
}
