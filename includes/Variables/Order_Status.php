<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Order_Status
 */
class Variable_Order_Status extends Variable {

	/**
	 * Load the admin details for this variable
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the status of the order.', 'automatewoo' );
		$this->add_parameter_select_field(
			'format',
			__( 'Choose whether to display the order status label or slug.', 'automatewoo' ),
			[
				''     => __( 'Label', 'automatewoo' ),
				'slug' => __( 'Slug', 'automatewoo' ),
			]
		);
	}

	/**
	 * Get the Order Status Name
	 *
	 * @param \WC_Order $order The Order to get the status
	 * @param array     $parameters
	 *
	 * @return string The Order Status Name
	 */
	public function get_value( $order, $parameters = [] ) {
		if ( isset( $parameters['format'] ) && 'slug' === $parameters['format'] ) {
			return $order->get_status();
		}

		return wc_get_order_status_name( $order->get_status() );
	}
}
