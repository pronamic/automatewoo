<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Teacher_Full_Name
 *
 * @since 5.6.10
 */
class Variable_Sensei_Teacher_Full_Name extends Variable {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the teacher's full name.", 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_User $teacher    \WP_User Object
	 * @param array    $parameters Variable parameters
	 * @return string
	 */
	public function get_value( $teacher, $parameters ) {
		return aw_get_full_name( $teacher );
	}
}
