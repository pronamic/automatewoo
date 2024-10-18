<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable_Customer_Username class.
 *
 * @since 4.6.0
 */
class Variable_Customer_Username extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the customer's username. This will be blank for guest customers.", 'automatewoo' );
	}

	/**
	 * Get the variable's value.
	 *
	 * @param Customer $customer
	 *
	 * @return string
	 */
	public function get_value( $customer ) {
		if ( $customer->is_registered() ) {
			$user = $customer->get_user();

			if ( $user ) {
				return $user->user_login;
			}
		}

		return '';
	}
}
