<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Guest_Last_Name
 */
class Variable_Guest_Last_Name extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the guest's last name. Please note that guests will not always have a last name stored.", 'automatewoo');
	}


	/**
	 * @param $guest Guest
	 * @param $parameters
	 * @return string
	 */
	function get_value( $guest, $parameters ) {
		return $guest->get_last_name();
	}
}
