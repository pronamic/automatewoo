<?php

namespace AutomateWoo;

/**
 * @class Order_Helper
 */
class Order_Helper {


	/**
	 * Default constructor.
	 */
	public function __construct() {
		if ( AUTOMATEWOO_DISABLE_ASYNC_ORDER_STATUS_CHANGED ) {
			// if not using async status change hook refresh customer totals before triggers fire
			add_action( 'woocommerce_order_status_changed', [ $this, 'maybe_refresh_customer_totals' ], 5, 3 );
		}

		add_action( 'woocommerce_before_order_object_save', [ $this, 'maybe_delete_shop_order_transients' ], 10 );
	}


	/**
	 * In WC_Abstract_Order::update_status() customer totals refresh after change status hooks have fired.
	 * We need access to these for order triggers so manually refresh early.
	 * In the future order triggers could fire async which should solve this issue
	 *
	 * @param int    $order_id
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function maybe_refresh_customer_totals( $order_id, $old_status, $new_status ) {

		if ( ! in_array( $new_status, [ 'completed', 'processing', 'on-hold', 'cancelled' ], true ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$customer = Customer_Factory::get_by_order( $order );

		if ( $customer ) {
			$customer->delete_meta( 'order_count' );
			$customer->delete_meta( 'total_spent' );
		}

		$user_id = $order->get_user_id();

		if ( $user_id ) {
			delete_user_meta( $user_id, '_money_spent' );
			delete_user_meta( $user_id, '_order_count' );
			delete_user_meta( $user_id, '_aw_order_count' );
			delete_user_meta( $user_id, '_aw_order_ids' );
		}
	}


	/**
	 * Delete transients only if some fields have changed.
	 *
	 * @param \WC_Order $order
	 */
	public function maybe_delete_shop_order_transients( $order ) {

		// Only delete the transients if one of the following fields has changed
		$changes = [
			'total',
			'status',
			'billing',
			'customer_id',
		];

		$new_changes = array_intersect( $changes, array_keys( $order->get_changes() ) );

		if ( ! $order || empty( $new_changes ) ) {
			return;
		}

		// If the only change is billings but not email, don't delete the transients
		if ( count( $new_changes ) === 1 && isset( $order->get_changes()['billing'] ) && ! isset( $order->get_changes()['billing']['email'] ) ) {
			return;
		}

		// If there have been changes to the customer ID or billing email, remove the transients associated with the previous customer.
		if ( isset( $order->get_changes()['customer_id'] ) || ( isset( $order->get_changes()['billing']['email'] ) ) ) {
			$old_order = new \WC_Order();
			$old_order->set_props( $order->get_base_data() );
			$this->delete_shop_order_transients( $old_order );
		}

		$this->delete_shop_order_transients( $order );
	}


	/**
	 * Delete transients for a shop order.
	 *
	 * @param \WC_Order $order
	 */
	public function delete_shop_order_transients( $order ) {

		// Set $create param to false to prevent creating a new customer at this point
		$customer = Customer_Factory::get_by_order( $order, false );
		if ( $customer ) {
			$customer->delete_meta( 'order_count' );
			$customer->delete_meta( 'total_spent' );
		}

		$user_id = $order->get_user_id();

		if ( $user_id ) {
			delete_user_meta( $user_id, '_aw_order_count' );
			delete_user_meta( $user_id, '_aw_order_ids' );
		}
	}


	/**
	 * LEGACY - use Customer object instead of this function
	 *
	 * @deprecated
	 *
	 * @param \WC_Order $order
	 * @return Order_Guest|\WP_User|false
	 */
	public function prepare_user_data_item( $order ) {

		if ( ! $order ) {
			return false;
		}

		$user = $order->get_user();

		if ( $user ) {
			// ensure first and last name are set
			if ( ! $user->first_name ) {
				$user->first_name = $order->get_billing_first_name();
			}
			if ( ! $user->last_name ) {
				$user->last_name = $order->get_billing_last_name();
			}
			if ( ! $user->billing_phone ) {
				$user->billing_phone = $order->get_billing_phone();
			}
		} else {
			// order placed by a guest
			$user = new Order_Guest( $order );
		}

		return $user;
	}
}
