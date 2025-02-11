<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Shop_Admin_Email
 */
class Variable_Shop_Admin_Email extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Display the site admin email. Note: You can use this variable in the To field when sending emails.', 'automatewoo' );
	}

	/**
	 * @param array $parameters
	 * @return string
	 */
	public function get_value( $parameters ) {
		return get_bloginfo( 'admin_email' );
	}
}
