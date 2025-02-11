<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Guest_Generate_Coupon
 */
class Variable_Guest_Generate_Coupon extends Variable_Abstract_Generate_Coupon {

	/**
	 * @param Guest    $guest
	 * @param array    $parameters
	 * @param Workflow $workflow
	 * @return string
	 */
	public function get_value( $guest, $parameters, $workflow ) {
		return $this->generate_coupon( $guest->get_email(), $parameters, $workflow );
	}
}
