<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_Shipping_Method
 */
class Variable_Order_Shipping_Method extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->add_parameter_select_field(
			'format',
			__( 'Choose whether to display the title or the ID of the shipping method.', 'automatewoo' ),
			[
				''   => __( 'Title', 'automatewoo' ),
				'id' => __( 'ID', 'automatewoo' ),
			],
			false
		);

		$this->description = __( 'Displays the shipping method for the order.', 'automatewoo' );
	}

	/**
	 * @param \WC_Order $order
	 * @param array     $parameters
	 * @return string
	 */
	public function get_value( $order, $parameters ) {

		$display = isset( $parameters['format'] ) ? $parameters['format'] : 'title';

		switch ( $display ) {
			case 'id':
				// get id of first method
				$methods = $order->get_shipping_methods();
				$method  = current( $methods );
				return $method->get_method_id();
			case 'title':
				return $order->get_shipping_method();
		}
	}
}
