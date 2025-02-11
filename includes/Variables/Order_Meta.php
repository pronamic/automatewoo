<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_Meta
 */
class Variable_Order_Meta extends Variable_Abstract_Meta {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the value of an order custom field.', 'automatewoo' );
	}

	/**
	 * @param \WC_Order $order
	 * @param array     $parameters
	 * @return string
	 */
	public function get_value( $order, $parameters ) {
		if ( $parameters['key'] ) {
			return (string) $order->get_meta( $parameters['key'] );
		}
		return '';
	}
}
