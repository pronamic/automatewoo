<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Guest_Generate_Coupon
 */
class Variable_Guest_Generate_Coupon extends Variable_Abstract_Generate_Coupon {


	/**
	 * @param $guest Guest
	 * @param $parameters
	 * @param $workflow
	 * @return string
	 */
	function get_value( $guest, $parameters, $workflow ) {
		return $this->generate_coupon( $guest->get_email(), $parameters, $workflow );
	}
}
