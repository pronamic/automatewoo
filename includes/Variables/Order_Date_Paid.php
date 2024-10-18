<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable_Abstract_Datetime;

defined( 'ABSPATH' ) || exit;

/**
 * Order_Date_Paid class.
 *
 * @since 4.8.0
 */
class Order_Date_Paid extends Variable_Abstract_Datetime {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();

		$this->description  = __( 'Displays the date the order was paid for.', 'automatewoo' );
		$this->description .= ' ' . $this->_desc_format_tip;
	}

	/**
	 * Get the variable's value.
	 *
	 * @param \WC_Order $order
	 * @param array     $parameters
	 *
	 * @return string
	 */
	public function get_value( $order, $parameters ) {
		return $this->format_datetime( $order->get_date_paid(), $parameters );
	}
}
