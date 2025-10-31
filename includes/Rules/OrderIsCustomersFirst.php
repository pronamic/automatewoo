<?php

namespace AutomateWoo\Rules;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * OrderIsCustomersFirst rule class.
 */
class OrderIsCustomersFirst extends Abstract_Bool {

	/** @var string */
	public $data_item = 'order';


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( "Order - Is Customer's First", 'automatewoo' );
	}


	/**
	 * Validate rule.
	 *
	 * @param WC_Order $order   The order object.
	 * @param string   $compare The comparison operator (not used).
	 * @param string   $value   The value to compare against ('yes' or 'no').
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		$customer = [ $order->get_billing_email() ];

		if ( $order->get_user_id() ) {
			$customer[] = $order->get_user_id();
		}

		// Exclude draft orders to avoid misjudgment caused by potential leftover
		// draft orders from  Checkout Blocks.
		$statuses = array_diff(
			array_keys( wc_get_order_statuses() ),
			aw_get_draft_order_statuses()
		);

		$orders = wc_get_orders(
			[
				'type'         => 'shop_order',
				'status'       => $statuses,
				'customer'     => $customer,
				'limit'        => 1,
				'return'       => 'ids',
				'exclude'      => [ $order->get_id() ],
				'date_created' => '<' . $order->get_date_created()->getTimestamp(),
			]
		);

		$is_first = empty( $orders );

		switch ( $value ) {
			case 'yes':
				return $is_first;

			case 'no':
				return ! $is_first;
		}
	}
}
