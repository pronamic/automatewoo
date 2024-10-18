<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Membership_Status
 */
class Variable_Membership_Status extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the status of the membership.", 'automatewoo');
	}


	/**
	 * @param $membership \WC_Memberships_User_Membership
	 * @param $parameters
	 * @return string
	 */
	function get_value( $membership, $parameters ) {
		return wc_memberships_get_user_membership_status_name( $membership->get_status() );
	}

}
