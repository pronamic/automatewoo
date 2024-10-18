<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle Order Re-Order URL variable
 *
 * @class Variable_Order_Reorder_Url
 * @since 2.8.6
 */
class Variable_Order_Reorder_Url extends Variable {


	/**
	 * Load admin details for this variable
	 */
	public function load_admin_details() {
		$this->description = __( "Displays a reorder URL for the order. When clicked all items from the order will be added to the user's cart.", 'automatewoo' );
	}


	/**
	 * Get the Order Re-Order URL
	 *
	 * @param \WC_Order $order The order
	 * @param array     $parameters The variable parameters
	 * @return string THe Order Re-Order URL
	 */
	public function get_value( $order, $parameters ) {
		// SEMGREP WARNING EXPLANATION
		// URL is escaped. However, Semgrep only considers esc_url as valid.
		return esc_url_raw(
			add_query_arg(
				[
					'aw-action'    => 'reorder',
					'aw-order-key' => $order->get_order_key(),
				],
				wc_get_page_permalink( 'cart' )
			)
		);
	}
}
