<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Memberships_Abstract
 * @since 2.8
 */
abstract class Action_Memberships_Abstract extends Action {

	/**
	 * Method to set the action's admin props.
	 */
	public function load_admin_details() {
		$this->group = __( 'Membership', 'automatewoo' );
	}
}
