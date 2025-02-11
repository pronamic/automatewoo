<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Membership_Date_Started
 */
class Variable_Membership_Date_Started extends Variable_Abstract_Datetime {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description  = __( "Displays the membership start date in your website's timezone.", 'automatewoo' );
		$this->description .= ' ' . $this->_desc_format_tip;
	}

	/**
	 * @param \WC_Memberships_User_Membership $membership
	 * @param array                           $parameters
	 * @return string
	 */
	public function get_value( $membership, $parameters ) {
		return $this->format_datetime( $membership->get_local_start_date(), $parameters );
	}
}
