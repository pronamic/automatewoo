<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Group_ID
 *
 * @since 5.6.10
 */
class Variable_Sensei_Group_ID extends Variable {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the group's ID.", 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_Post $group      \WP_Post Object
	 * @param array    $parameters Variable parameters
	 * @return string
	 */
	public function get_value( $group, $parameters ) {
		return $group->ID;
	}
}
