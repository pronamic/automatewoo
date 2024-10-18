<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Membership_ID
 */
class Variable_Membership_ID extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the ID of the membership.", 'automatewoo');
	}


	/**
	 * @param $membership \WC_Memberships_User_Membership
	 * @param $parameters
	 * @return string
	 */
	function get_value( $membership, $parameters ) {
		return $membership->get_id();
	}

}
