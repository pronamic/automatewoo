<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Membership_ID
 */
class Variable_Membership_ID extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the ID of the membership.', 'automatewoo' );
	}

	/**
	 * @param \WC_Memberships_User_Membership $membership
	 * @param array                           $parameters
	 * @return string
	 */
	public function get_value( $membership, $parameters ) {
		return $membership->get_id();
	}
}
