<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Course_Teacher_Emails
 *
 * @since 6.5.0
 */
class Variable_Sensei_Course_Teacher_Emails extends Variable_Abstract_Sensei_Course_Teachers {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays a comma-separated list of course teacher email addresses, including co-teachers.', 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_Post $course     \WP_Post Object.
	 * @param array    $parameters Variable parameters.
	 * @return string
	 */
	public function get_value( $course, $parameters ) {
		$emails = [];

		foreach ( $this->get_course_teachers( $course ) as $teacher ) {
			if ( $teacher->user_email ) {
				$emails[] = $teacher->user_email;
			}
		}

		return implode( ', ', array_unique( $emails ) );
	}
}
