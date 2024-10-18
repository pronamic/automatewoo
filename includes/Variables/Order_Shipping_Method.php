<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Shipping_Method
 */
class Variable_Order_Shipping_Method extends Variable {


	function load_admin_details() {
		$this->add_parameter_select_field('format', __( "Choose whether to display the title or the ID of the shipping method.", 'automatewoo'), [
			'' => __( "Title", 'automatewoo' ),
			'id' => __( "ID", 'automatewoo' )
		], false );

		$this->description = __( "Displays the shipping method for the order.", 'automatewoo');
	}


	/**
	 * @param $order \WC_Order
	 * @param $parameters array
	 * @return string
	 */
	function get_value( $order, $parameters ) {

		$display = isset( $parameters['format'] ) ? $parameters['format'] : 'title';

		switch ( $display ) {
			case 'id':
				// get id of first method
				$methods = $order->get_shipping_methods();
				$method = current( $methods );
				return $method->get_method_id();
				break;
			case 'title':
				return $order->get_shipping_method();
				break;
		}
	}

}
