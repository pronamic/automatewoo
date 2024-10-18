<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class for the membership.meta variable.
 *
 * @class   Variable_Membership_Meta
 * @package AutomateWoo
 */
class Variable_Membership_Meta extends Variable_Abstract_Meta {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( "Displays a memberships's custom field.", 'automatewoo' );
	}

	/**
	 * Get the variable's value.
	 *
	 * @param \WC_Memberships_User_Membership $membership The membership object.
	 * @param array                           $parameters The variable's parameters.
	 *
	 * @return string
	 */
	public function get_value( $membership, $parameters ) {
		if ( $parameters['key'] ) {
			return (string) get_post_meta( $membership->get_id(), $parameters['key'], true );
		}
		return '';
	}
}
