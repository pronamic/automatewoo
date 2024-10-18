<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Membership_Plan_ID
 */
class Variable_Membership_Plan_ID extends Variable {


	function load_admin_details() {
		$this->description = __( "Displays the plan ID of the membership.", 'automatewoo');
	}

	/**
	 * @param $membership \WC_Memberships_User_Membership
	 * @param $parameters
	 * @return string
	 */
	function get_value( $membership, $parameters ) {
		if ( ! $plan = $membership->get_plan() ) {
			return false;
		}
		return $plan->get_id();
	}

}
