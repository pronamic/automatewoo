<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Guest_Email
 */
class Variable_Guest_Email extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the guest’s email address. Note: You can use this variable in the To field when sending emails.", 'automatewoo');
	}


	/**
	 * @param $guest Guest
	 * @param $parameters
	 * @return string
	 */
	function get_value( $guest, $parameters ) {
		return $guest->get_email();
	}
}
