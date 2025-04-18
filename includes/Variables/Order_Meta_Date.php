<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_Meta_Date
 */
class Variable_Order_Meta_Date extends Variable_Abstract_Datetime {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->add_parameter_text_field( 'key', __( 'The meta_key of the field you would like to display.', 'automatewoo' ), true );
		parent::load_admin_details();
		$this->description  = _x( "Displays the value of a date-based meta field in your site's timezone. The meta field must be stored in UTC time in MYSQL or UNIX timestamp format.", 'data type e.g. order, product', 'automatewoo' );
		$this->description .= ' ' . $this->_desc_format_tip;
	}


	/**
	 * @param \WC_Order $order
	 * @param array     $parameters
	 * @return string|bool
	 */
	public function get_value( $order, $parameters ) {
		if ( ! $parameters['key'] ) {
			return false;
		}

		$value = Clean::string( $order->get_meta( $parameters['key'] ) );

		return $this->format_datetime( $value, $parameters, true );
	}
}
