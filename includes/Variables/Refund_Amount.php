<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable Refund Amount Class.
 *
 * @class Variable_Refund_Amount
 *
 * @since 5.6.2
 */
class Variable_Refund_Amount extends Variable_Abstract_Price {

	/**
	 * Load Admin Details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the refund amount.', 'automatewoo' );
	}


	/**
	 * Get Value Method.
	 *
	 * @param \WC_Order_Refund $refund
	 * @param array            $parameters
	 *
	 * @return string
	 */
	public function get_value( $refund, $parameters ) {
		return parent::format_amount( $refund->get_amount(), $parameters );
	}
}
