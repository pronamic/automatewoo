<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Course_Students
 *
 * @since 5.6.10
 */
class Variable_Sensei_Course_Students_Admin_URL extends Variable {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the admin panel URL to view course students.', 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_Post $course     \WP_Post Object
	 * @param array    $parameters Variable parameters
	 * @return string
	 */
	public function get_value( $course, $parameters ) {
		if ( ! $course instanceof \WP_Post ) {
			return '';
		}

		// SEMGREP WARNING EXPLANATION
		// URL is escaped. However, Semgrep only considers esc_url as valid.
		return esc_url_raw(
			add_query_arg(
				array(
					'page'      => 'sensei_learners',
					'course_id' => $course->ID,
					'view'      => 'learners',
				),
				admin_url( 'admin.php' )
			)
		);
	}
}
