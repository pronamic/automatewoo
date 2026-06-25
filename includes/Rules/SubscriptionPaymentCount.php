<?php

namespace AutomateWoo\Rules;

use WC_Subscription;

defined( 'ABSPATH' ) || exit;

/**
 * SubscriptionPaymentCount rule class.
 */
class SubscriptionPaymentCount extends Abstract_Number {

	/** @var string */
	public $data_item = 'subscription';

	/** @var bool */
	public $has_multiple_value_fields = true;

	/** @var bool */
	public $has_payment_count_scope = true;

	/** @var bool */
	public $support_floats = false;

	const COUNT_SCOPE_CURRENT                 = 'current';
	const COUNT_SCOPE_INCLUDE_RESUBSCRIPTIONS = 'include_resubscriptions';

	/**
	 * Initializer
	 *
	 * @return void
	 */
	public function init() {
		$this->title = __( 'Subscription - Payment Count', 'automatewoo' );
	}


	/**
	 * @param WC_Subscription $subscription
	 * @param string          $compare
	 * @param mixed           $value
	 * @return bool
	 */
	public function validate( $subscription, $compare, $value ) {
		// Offset compares store their own {multiple, offset} array and do not use the count scope.
		if ( $this->is_offset_compare_type( $compare ) ) {
			$payment_count = $this->get_payment_count( $subscription, false );

			return $this->validate_number( $payment_count, $compare, $value );
		}

		$payment_count = $this->get_payment_count( $subscription, $this->should_include_previous_resubscriptions( $value ) );

		return $this->validate_number( $payment_count, $compare, $this->get_rule_count( $value ) );
	}

	/**
	 * Whether the compare type uses the {multiple, offset} value structure.
	 *
	 * @since 6.6.0
	 *
	 * @param string $compare
	 *
	 * @return bool
	 */
	protected function is_offset_compare_type( $compare ) {
		return in_array( $compare, [ 'multiple_with_offset', 'not_multiple_with_offset' ], true );
	}

	/**
	 * Sanitizes the field value.
	 *
	 * @param string|array $value
	 *
	 * @return string|array
	 */
	public function sanitize_value( $value ) {
		if ( ! is_array( $value ) || $this->is_offset_value( $value ) ) {
			return parent::sanitize_value( $value );
		}

		return [
			'count'       => parent::sanitize_value( isset( $value['count'] ) ? $value['count'] : '' ),
			'count_scope' => $this->sanitize_count_scope( isset( $value['count_scope'] ) ? $value['count_scope'] : '' ),
		];
	}

	/**
	 * Whether the stored value uses the {multiple, offset} structure.
	 *
	 * @since 6.6.0
	 *
	 * @param array $value
	 *
	 * @return bool
	 */
	protected function is_offset_value( $value ) {
		return is_array( $value ) && ( isset( $value['multiple'] ) || isset( $value['offset'] ) );
	}

	/**
	 * Formats a rule's value for display in the rules UI.
	 *
	 * @param string|array $value
	 *
	 * @return string|array
	 */
	public function format_value( $value ) {
		if ( ! is_array( $value ) || $this->is_offset_value( $value ) ) {
			return parent::format_value( $value );
		}

		return [
			'count'       => parent::format_value( isset( $value['count'] ) ? $value['count'] : '' ),
			'count_scope' => $this->sanitize_count_scope( isset( $value['count_scope'] ) ? $value['count_scope'] : '' ),
		];
	}

	/**
	 * Get rule count from saved scalar values or the new structured value.
	 *
	 * @param string|array $value
	 *
	 * @return string
	 */
	protected function get_rule_count( $value ) {
		return is_array( $value ) && isset( $value['count'] ) ? $value['count'] : $value;
	}

	/**
	 * Whether the rule should include payment counts from previous resubscriptions.
	 *
	 * @param string|array $value
	 *
	 * @return bool
	 */
	protected function should_include_previous_resubscriptions( $value ) {
		return is_array( $value ) && isset( $value['count_scope'] ) && self::COUNT_SCOPE_INCLUDE_RESUBSCRIPTIONS === $value['count_scope'];
	}

	/**
	 * Sanitize the payment count scope value.
	 *
	 * @param string $count_scope
	 *
	 * @return string
	 */
	protected function sanitize_count_scope( $count_scope ) {
		return self::COUNT_SCOPE_INCLUDE_RESUBSCRIPTIONS === $count_scope ? self::COUNT_SCOPE_INCLUDE_RESUBSCRIPTIONS : self::COUNT_SCOPE_CURRENT;
	}

	/**
	 * Get the payment count for a subscription.
	 *
	 * @param WC_Subscription $subscription
	 * @param bool            $include_previous_resubscriptions
	 * @param array           $seen_subscription_ids
	 *
	 * @return int
	 */
	protected function get_payment_count( $subscription, $include_previous_resubscriptions = false, $seen_subscription_ids = [] ) {
		$payment_count = $this->get_current_subscription_payment_count( $subscription );

		if ( ! $include_previous_resubscriptions ) {
			return $payment_count;
		}

		$seen_subscription_ids[ $subscription->get_id() ] = true;

		foreach ( $this->get_previous_resubscribed_subscriptions( $subscription ) as $previous_subscription ) {
			if ( ! $previous_subscription instanceof WC_Subscription ) {
				continue;
			}

			$previous_subscription_id = $previous_subscription->get_id();

			if ( isset( $seen_subscription_ids[ $previous_subscription_id ] ) ) {
				continue;
			}

			$payment_count += $this->get_payment_count( $previous_subscription, true, $seen_subscription_ids );
		}

		return $payment_count;
	}

	/**
	 * Get the payment count for the current subscription only.
	 *
	 * @param WC_Subscription $subscription
	 *
	 * @return int
	 */
	protected function get_current_subscription_payment_count( $subscription ) {
		// Method changed in WCS 2.6.
		return is_callable( [ $subscription, 'get_payment_count' ] ) ? $subscription->get_payment_count() : $subscription->get_completed_payment_count();
	}

	/**
	 * Get previous subscriptions connected to the current subscription through its resubscribe order.
	 *
	 * @param WC_Subscription $subscription
	 *
	 * @return WC_Subscription[]
	 */
	protected function get_previous_resubscribed_subscriptions( $subscription ) {
		if ( ! function_exists( 'wcs_get_subscriptions_for_resubscribe_order' ) ) {
			return [];
		}

		$parent_order = is_callable( [ $subscription, 'get_parent' ] ) ? $subscription->get_parent() : false;

		if ( ! $parent_order && is_callable( [ $subscription, 'get_parent_id' ] ) ) {
			$parent_order = wc_get_order( $subscription->get_parent_id() );
		}

		if ( ! $parent_order ) {
			return [];
		}

		return wcs_get_subscriptions_for_resubscribe_order( $parent_order );
	}
}
