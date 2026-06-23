<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Course_Teacher_Full_Names
 *
 * @since 6.5.0
 */
class Variable_Sensei_Course_Teacher_Full_Names extends Variable_Abstract_Sensei_Course_Teachers {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays a comma-separated list of course teacher full names, including co-teachers.', 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_Post $course     \WP_Post Object.
	 * @param array    $parameters Variable parameters.
	 * @return string
	 */
	public function get_value( $course, $parameters ) {
		$names = [];

		foreach ( $this->get_course_teachers( $course ) as $teacher ) {
			$name = aw_get_full_name( $teacher );
			if ( $name ) {
				$names[] = $name;
			}
		}

		return implode( ', ', array_unique( $names ) );
	}
}
