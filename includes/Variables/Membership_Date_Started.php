<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Membership_Date_Started
 */
class Variable_Membership_Date_Started extends Variable_Abstract_Datetime {


	function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( "Displays the membership start date in your website's timezone.", 'automatewoo' );
		$this->description .= ' ' . $this->_desc_format_tip;
	}


	/**
	 * @param $membership \WC_Memberships_User_Membership
	 * @param $parameters
	 * @return string
	 */
	function get_value( $membership, $parameters ) {
		return $this->format_datetime( $membership->get_local_start_date(), $parameters );
	}

}
