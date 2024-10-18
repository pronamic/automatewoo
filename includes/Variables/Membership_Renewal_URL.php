<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Membership_Renewal_URL
 * @since 4.2
 */
class Variable_Membership_Renewal_URL extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the renewal URL for the membership.", 'automatewoo');
	}


	/**
	 * @param $membership \WC_Memberships_User_Membership
	 * @param $parameters
	 * @return string
	 */
	function get_value( $membership, $parameters ) {
		return esc_url( $membership->get_renew_membership_url() );
	}

}
