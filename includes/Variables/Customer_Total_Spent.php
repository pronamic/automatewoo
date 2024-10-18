<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable Customer Total Spent Variable.
 *
 * @class Variable_Customer_Total_Spent
 */
class Variable_Customer_Total_Spent extends Variable_Abstract_Price {

	/**
	 * Load Admin Details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the total amount the customer has spent.', 'automatewoo' );
	}

	/**
	 * Get Value Method.
	 *
	 * @param \WC_Customer $customer
	 * @param array        $parameters
	 *
	 * @return string
	 */
	public function get_value( $customer, $parameters ) {
		return parent::format_amount( $customer->get_total_spent(), $parameters );
	}
}
