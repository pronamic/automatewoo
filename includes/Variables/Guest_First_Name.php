<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Guest_First_Name
 */
class Variable_Guest_First_Name extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the guest's first name. Please note that guests will not always have a first name stored.", 'automatewoo');
	}

	/**
	 * @param $guest Guest
	 * @param $parameters
	 * @return string
	 */
	function get_value( $guest, $parameters ) {
		return $guest->get_first_name();
	}
}
