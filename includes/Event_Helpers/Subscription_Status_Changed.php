<?php

namespace AutomateWoo\Event_Helpers;

/**
 * @class Subscription_Status_Changed
 */
class Subscription_Status_Changed {

	/** @var bool */
	public static $doing_payment = false;

	/** @var int */
	private static $early_renewal_subscription_id = 0;


	/**
	 * Initializer function
	 */
	public static function init() {
		// Whenever a renewal payment is due subscription is placed on hold and then back to active if successful
		// Block this trigger while this happens
		add_action( 'woocommerce_scheduled_subscription_payment', [ __CLASS__, 'before_payment' ], 0, 0 );
		add_action( 'woocommerce_scheduled_subscription_payment', [ __CLASS__, 'after_payment' ], 1000 );
		add_action( 'woocommerce_order_status_changed', [ __CLASS__, 'before_early_renewal_payment' ], 4, 4 );
		add_action( 'woocommerce_order_status_changed', [ __CLASS__, 'after_early_renewal_payment' ], 6, 0 );

		add_action( 'woocommerce_subscription_status_updated', [ __CLASS__, 'status_changed' ], 10, 3 );
	}


	/**
	 * Function to run before the payment is done
	 */
	public static function before_payment() {
		self::$doing_payment = true;
	}


	/**
	 * @param int $subscription_id
	 */
	public static function after_payment( $subscription_id ) {

		self::$doing_payment = false;

		$subscription = wcs_get_subscription( $subscription_id );

		if ( $subscription && ! $subscription->has_status( 'active' ) ) {
			// if status was changed (no longer active) during payment trigger now
			self::status_changed( $subscription, $subscription->get_status(), 'active' );
		}
	}

	/**
	 * Block subscription status triggers while an early renewal payment is processed.
	 *
	 * @param int       $order_id Order ID.
	 * @param string    $old_status Old order status.
	 * @param string    $new_status New order status.
	 * @param \WC_Order $order Order object.
	 */
	public static function before_early_renewal_payment( $order_id, $old_status, $new_status, $order = null ) {
		$subscription_id = self::get_early_renewal_payment_subscription_id( $order_id, $old_status, $new_status, $order );

		if ( ! $subscription_id ) {
			return;
		}

		self::$doing_payment                 = true;
		self::$early_renewal_subscription_id = $subscription_id;
	}

	/**
	 * Re-enable subscription status triggers after an early renewal payment has been processed.
	 */
	public static function after_early_renewal_payment() {
		if ( ! self::$early_renewal_subscription_id ) {
			return;
		}

		$subscription_id                     = self::$early_renewal_subscription_id;
		self::$early_renewal_subscription_id = 0;
		self::$doing_payment                 = false;

		$subscription = wcs_get_subscription( $subscription_id );

		if ( $subscription && ! $subscription->has_status( 'active' ) ) {
			// if status was changed (no longer active) during payment trigger now
			self::status_changed( $subscription, $subscription->get_status(), 'active' );
		}
	}

	/**
	 * Get the subscription ID for an early renewal payment order transition.
	 *
	 * @param int            $order_id Order ID.
	 * @param string         $old_status Old order status.
	 * @param string         $new_status New order status.
	 * @param \WC_Order|null $order Order object.
	 *
	 * @return int
	 */
	private static function get_early_renewal_payment_subscription_id( $order_id, $old_status, $new_status, $order = null ) {
		if ( in_array( $new_status, [ 'cancelled', 'refunded' ], true ) ) {
			return 0;
		}

		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order || ! is_callable( [ $order, 'get_meta' ] ) ) {
			return 0;
		}

		if ( ! self::order_contains_early_renewal( $order ) ) {
			return 0;
		}

		$valid_payment_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment', [ 'pending', 'on-hold', 'failed' ], $order );

		if ( ! in_array( $old_status, $valid_payment_statuses, true ) ) {
			return 0;
		}

		return absint( $order->get_meta( '_subscription_renewal_early' ) );
	}

	/**
	 * Check whether an order is an early renewal order.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return bool
	 */
	private static function order_contains_early_renewal( $order ) {
		if ( function_exists( 'wcs_order_contains_early_renewal' ) ) {
			return wcs_order_contains_early_renewal( $order );
		}

		return (bool) $order->get_meta( '_subscription_renewal_early' );
	}


	/**
	 * @param \WC_Subscription $subscription
	 * @param string           $new_status
	 * @param string           $old_status
	 */
	public static function status_changed( $subscription, $new_status, $old_status ) {

		if ( self::$doing_payment || ! $subscription ) {
			return;
		}

		do_action( 'automatewoo/subscription/status_changed', $subscription->get_id(), $new_status, $old_status );
	}
}
