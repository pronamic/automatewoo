<?php
namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Refund_Reason
 *
 * @since 5.6.2
 */
class Variable_Refund_Reason extends Variable {

	/**
	 * Load Admin Details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the refund reason.', 'automatewoo' );
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
		return $refund->get_reason();
	}
}
