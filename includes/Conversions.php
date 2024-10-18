<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Conversions
 * @since 2.1
 */
class Conversions {


	/**
	 * Max number of days that a purchase to be considered a conversion
	 * @return int
	 */
	static function get_conversion_window() {
		return absint( apply_filters( 'automatewoo_conversion_window', AW()->options()->conversion_window ) );
	}


	/**
	 * @param int $order_id
	 */
	static function check_order_for_conversion( $order_id ) {

		$order = wc_get_order( Clean::id( $order_id ) );
		if ( ! $order ) {
			return;
		}

		$customer = Customer_Factory::get_by_order( $order );
		if ( ! $customer ) {
			return;
		}

		$conversion_window_end = aw_normalize_date( $order->get_date_created() ); // convert to UTC

		if ( ! $conversion_window_end ) {
			return;
		}

		$conversion_window_start = clone $conversion_window_end;
		$conversion_window_start->modify( ( -1 * self::get_conversion_window() ) . ' days' );

		if ( ! $logs = self::get_logs_by_customer( $customer, $conversion_window_start, $conversion_window_end ) ) {
			return;
		}

		foreach ( $logs as $log ) {
			if ( self::is_valid_conversion( $order, $log ) ) {
				$order->update_meta_data( '_aw_conversion', $log->get_workflow_id() );
				$order->update_meta_data( '_aw_conversion_log', $log->get_id() );
				$order->save();

				/**
				 * Fires when a conversion is recorded for an order.
				 *
				 * @since 4.9.0
				 */
				do_action( 'automatewoo/conversion/recorded', $order, $log );

				break; // break loop here so that only one log/workflow gets the conversion
			}
		}
	}


	/**
	 * @param Customer $customer
	 * @param DateTime $conversion_window_start
	 * @param DateTime $conversion_window_end
	 * @return Log[]
	 */
	static function get_logs_by_customer( $customer, $conversion_window_start, $conversion_window_end ) {
		$query = new Log_Query();
		$query->where( 'conversion_tracking_enabled', true );
		$query->where_customer_or_legacy_user( $customer, true );
		$query->where_date_between( $conversion_window_start, $conversion_window_end );
		$query->set_ordering('date', 'DESC');

		return $query->get_results();
	}

	/**
	 * Checks if order and log (i.e. sent workflow) is a valid conversion.
	 *
	 * @since 4.8.0
	 *
	 * @param \WC_Order $order
	 * @param Log       $log
	 *
	 * @return bool
	 */
	protected static function is_valid_conversion( $order, $log ) {
		$is_valid = false;

		// Check the log shows that it has been opened i.e. has tracking data
		if ( $log->get_meta( 'tracking_data' ) ) {
			$is_valid = true;
		}

		// Exclude orders that were not created via the checkout from conversion tracking
		if ( $is_valid && ! self::is_order_created_via_checkout( $order ) ) {
			$is_valid = false;
		}

		// Confirm that the workflow is still active and has conversion tracking enabled
		if ( $is_valid ) {
			$workflow = $log->get_workflow();
			if ( ! $workflow || ! $workflow->is_active() || ! $workflow->is_conversion_tracking_enabled() ) {
				$is_valid = false;
			}
		}

		return (bool) apply_filters( 'automatewoo/conversions/is_valid_conversion', $is_valid, $order, $log->get_workflow_id(), $log );
	}

	/**
	 * Checks if the order was created via the checkout.
	 *
	 * We consider an order with the 'created_via' prop set to checkout or an order with a cart hash to be created via the checkout.
	 *
	 * This method should return false for automatic subscription renewals, rest API orders and POS orders.
	 *
	 * @since 4.8.0
	 *
	 * @param \WC_Order $order
	 *
	 * @return bool
	 */
	protected static function is_order_created_via_checkout( $order ) {
		return 'checkout' === $order->get_created_via() || $order->get_cart_hash();
	}

}
