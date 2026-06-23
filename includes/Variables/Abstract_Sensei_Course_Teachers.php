<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared helpers for Sensei course teacher variables.
 *
 * @since 6.5.0
 */
abstract class Variable_Abstract_Sensei_Course_Teachers extends Variable {

	/**
	 * Get teachers for a course.
	 *
	 * @param \WP_Post $course \WP_Post Object.
	 * @return \WP_User[]
	 */
	protected function get_course_teachers( $course ) {
		$teachers = [];

		foreach ( $this->get_course_teacher_ids( $course ) as $teacher_id ) {
			$teacher = get_user_by( 'id', $teacher_id );
			if ( $teacher instanceof \WP_User ) {
				$teachers[] = $teacher;
			}
		}

		return $teachers;
	}

	/**
	 * Get teacher IDs for a course.
	 *
	 * @param \WP_Post $course \WP_Post Object.
	 * @return int[]
	 */
	protected function get_course_teacher_ids( $course ) {
		$teacher_id  = absint( $course->post_author );
		$teacher_ids = [ $teacher_id ];

		// Include Sensei Pro co-teachers, which are stored as user meta and not exposed via the filter below.
		if ( class_exists( '\Sensei_Pro_Co_Teachers\Co_Teachers' ) ) {
			$teacher_ids = array_merge(
				$teacher_ids,
				\Sensei_Pro_Co_Teachers\Co_Teachers::instance()->get_course_coteachers_ids( $course->ID )
			);
		}

		$teacher_ids = apply_filters( 'sensei_email_course_teachers', $teacher_ids, $course->ID );

		if ( ! is_array( $teacher_ids ) ) {
			$teacher_ids = [ $teacher_id ];
		}

		$teacher_ids = array_filter( array_map( 'absint', $teacher_ids ) );

		return array_values( array_unique( $teacher_ids ) );
	}
}
