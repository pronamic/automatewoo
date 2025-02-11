<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Membership_Status
 */
class Variable_Membership_Status extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the status of the membership.', 'automatewoo' );
	}

	/**
	 * @param \WC_Memberships_User_Membership $membership
	 * @param array                           $parameters
	 * @return string
	 */
	public function get_value( $membership, $parameters ) {
		return wc_memberships_get_user_membership_status_name( $membership->get_status() );
	}
}
