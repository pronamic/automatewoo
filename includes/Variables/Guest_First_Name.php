<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Guest_First_Name
 */
class Variable_Guest_First_Name extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the guest's first name. Please note that guests will not always have a first name stored.", 'automatewoo' );
	}

	/**
	 * @param Guest $guest
	 * @param array $parameters
	 * @return string
	 */
	public function get_value( $guest, $parameters ) {
		return $guest->get_first_name();
	}
}
