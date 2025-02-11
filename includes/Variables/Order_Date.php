<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_Date
 */
class Variable_Order_Date extends Variable_Abstract_Datetime {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description  = __( 'Displays the date the order was placed.', 'automatewoo' );
		$this->description .= ' ' . $this->_desc_format_tip;
	}

	/**
	 * @param \WC_Order $order
	 * @param array     $parameters
	 * @return string
	 */
	public function get_value( $order, $parameters ) {
		return $this->format_datetime( $order->get_date_created(), $parameters );
	}
}
