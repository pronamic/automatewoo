<?php

namespace AutomateWoo\Event_Helpers;

/**
 * @class Subscription_Status_Changed
 */
class Subscription_Status_Changed {

	/** @var bool */
	public static $doing_payment = false;


	/**
	 * Initializer function
	 */
	public static function init() {
		// Whenever a renewal payment is due subscription is placed on hold and then back to active if successful
		// Block this trigger while this happens
		add_action( 'woocommerce_scheduled_subscription_payment', [ __CLASS__, 'before_payment' ], 0, 0 );
		add_action( 'woocommerce_scheduled_subscription_payment', [ __CLASS__, 'after_payment' ], 1000 );

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
