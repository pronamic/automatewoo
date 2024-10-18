<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Lesson_URL
 *
 * @since 5.6.10
 */
class Variable_Sensei_Lesson_URL extends Variable {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the Lesson's URL.", 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_Post $lesson     \WP_Post Object
	 * @param array    $parameters Variable parameters
	 * @return string
	 */
	public function get_value( $lesson, $parameters ) {
		return get_permalink( $lesson );
	}
}
