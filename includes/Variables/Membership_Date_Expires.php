<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Membership_Date_Expires
 */
class Variable_Membership_Date_Expires extends Variable_Abstract_Datetime {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description  = __( "Displays the membership expiry date in your website's timezone. Not all memberships will have an expiry.", 'automatewoo' );
		$this->description .= ' ' . $this->_desc_format_tip;
	}

	/**
	 * @param \WC_Memberships_User_Membership $membership
	 * @param array                           $parameters
	 * @return string
	 */
	public function get_value( $membership, $parameters ) {
		return $this->format_datetime( $membership->get_local_end_date(), $parameters );
	}
}
