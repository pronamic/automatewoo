<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Subscription last payment date rule.
 *
 * @class Subscription_Last_Payment_Date
 */
class Subscription_Last_Payment_Date extends Abstract_Date {

	/**
	 * Data item.
	 *
	 * @var string
	 */
	public $data_item = 'subscription';

	/**
	 * Subscription_Last_Payment_Date constructor.
	 */
	public function __construct() {
		$this->has_is_past_comparision = true;

		parent::__construct();
	}

	/**
	 * Init.
	 */
	public function init() {
		$this->title = __( 'Subscription - Last Payment Date', 'automatewoo' );
	}


	/**
	 * Validates our rule.
	 *
	 * @param \WC_Subscription $subscription The subscription object.
	 * @param string           $compare      Rule to compare.
	 * @param array|null       $value        The values we have to compare. Null is only allowed when $compare is is_not_set.
	 *
	 * @return bool
	 */
	public function validate( $subscription, $compare, $value = null ) {
		$last_paid = $this->get_last_successful_payment_date( $subscription );

		// When no order on the subscription has ever been paid, aw_normalize_date()
		// returns false and validate_date() falls through to the standard
		// validate_logical_empty_date() semantics shared by all date rules.
		return $this->validate_date( $compare, $value, aw_normalize_date( $last_paid ) );
	}

	/**
	 * Find the most recent successfully-paid order on the subscription.
	 *
	 * WCS's `$subscription->get_date( 'last_order_date_paid' )` only inspects the
	 * latest related order, so a failed or cancelled renewal causes it to return
	 * 0/empty even when prior renewals were paid. Walk renewals newest-first and
	 * fall back to the parent order so the rule reflects the actual last payment.
	 *
	 * @since 6.5.0
	 *
	 * @param \WC_Subscription $subscription The subscription object.
	 *
	 * @return string|\WC_DateTime|null UTC MySQL datetime string (from WCS) or WC_DateTime,
	 *                                  or null if no order has ever been paid.
	 */
	protected function get_last_successful_payment_date( $subscription ) {
		// Cheap path: trust WCS when its answer points to a real paid date.
		$date = $subscription->get_date( 'last_order_date_paid' );
		if ( $date && '0000-00-00 00:00:00' !== $date ) {
			return $date;
		}

		// Walk renewal orders newest-first looking for a real date_paid.
		$renewal_ids = $subscription->get_related_orders( 'ids', 'renewal' );
		rsort( $renewal_ids, SORT_NUMERIC );

		foreach ( $renewal_ids as $renewal_id ) {
			$order = wc_get_order( $renewal_id );
			if ( $order && $order->get_date_paid() ) {
				return $order->get_date_paid();
			}
		}

		// Fall back to the parent order — covers subscriptions with no successful
		// renewals but a paid initial order.
		$parent = $subscription->get_parent();
		if ( $parent && $parent->get_date_paid() ) {
			return $parent->get_date_paid();
		}

		return null;
	}
}
