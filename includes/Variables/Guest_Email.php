<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Guest_Email
 */
class Variable_Guest_Email extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the guest’s email address. Note: You can use this variable in the To field when sending emails.', 'automatewoo' );
	}

	/**
	 * @param Guest $guest
	 * @param array $parameters
	 * @return string
	 */
	public function get_value( $guest, $parameters ) {
		return $guest->get_email();
	}
}
