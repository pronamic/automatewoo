<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Membership_Plan_Name
 */
class Variable_Membership_Plan_Name extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the plan name of the membership.', 'automatewoo' );
	}

	/**
	 * @param \WC_Memberships_User_Membership $membership
	 * @param array                           $parameters
	 * @return string
	 */
	public function get_value( $membership, $parameters ) {
		$plan = $membership->get_plan();
		if ( ! $plan ) {
			return false;
		}
		return $plan->get_name();
	}
}
