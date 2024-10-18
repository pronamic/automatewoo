<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Payment_Method
 */
class Variable_Order_Payment_Method extends Variable {


	function load_admin_details() {
		$this->add_parameter_select_field('format', __( "Choose whether to display the title or the ID of the payment method.", 'automatewoo'), [
			'' => __( "Title", 'automatewoo' ),
			'id' => __( "ID", 'automatewoo' )
		], false );

		$this->description = __( "Displays the payment method for the order.", 'automatewoo');
	}


	/**
	 * @param \WC_Order $order
	 * @param array     $parameters
	 *
	 * @return string
	 */
	function get_value( $order, $parameters ) {

		$display = isset( $parameters['format'] ) ? $parameters['format'] : 'title';

		switch ( $display ) {
			case 'id':
				return $order->get_payment_method();
			case 'title':
				return $order->get_payment_method_title();
		}
	}
}
