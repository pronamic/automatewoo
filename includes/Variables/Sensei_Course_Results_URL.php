<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Course_Results_URL
 *
 * @since 5.6.10
 */
class Variable_Sensei_Course_Results_URL extends Variable {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the Course Results URL.', 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_Post $course     \WP_Post Object
	 * @param array    $parameters Variable parameters
	 * @return string
	 */
	public function get_value( $course, $parameters ) {
		if ( function_exists( 'Sensei' ) ) {
			return esc_url_raw( Sensei()->course_results->get_permalink( $course->ID ) );
		}
		return '';
	}
}
