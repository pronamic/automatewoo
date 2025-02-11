<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Membership_Renewal_URL
 * @since 4.2
 */
class Variable_Membership_Renewal_URL extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the renewal URL for the membership.', 'automatewoo' );
	}

	/**
	 * @param \WC_Memberships_User_Membership $membership
	 * @param array                           $parameters
	 * @return string
	 */
	public function get_value( $membership, $parameters ) {
		return esc_url( $membership->get_renew_membership_url() );
	}
}
