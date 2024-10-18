<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sensei_Workflow_Helper
 *
 * @since 5.6.10
 *
 * @package AutomateWoo
 */
class Sensei_Workflow_Helper {

	/**
	 * Get the Sensei group name.
	 *
	 * @return string
	 */
	public static function get_group_name() {
		return __( 'Sensei LMS', 'automatewoo' );
	}

	/**
	 * Get the Sensei Courses field.
	 *
	 * @return Fields\Sensei_Course
	 */
	public static function get_courses_field() {
		$field = new Fields\Sensei_Course();
		$field->set_name( 'sensei_courses' );
		$field->set_title( __( 'Courses', 'automatewoo' ) );
		$field->set_description( __( 'Select courses here to have this workflow trigger only for those specific courses. Leave blank to run for all courses.', 'automatewoo' ) );
		$field->set_multiple( true );

		return $field;
	}

	/**
	 * Get the Sensei Lessons field.
	 *
	 * @return Fields\Sensei_Lesson
	 */
	public static function get_lessons_field() {
		$field = new Fields\Sensei_Lesson();
		$field->set_name( 'sensei_lessons' );
		$field->set_title( __( 'Lessons', 'automatewoo' ) );
		$field->set_description( __( 'Select lessons here to have this workflow trigger only for those specific lessons. Leave blank to run for all lessons.', 'automatewoo' ) );
		$field->set_multiple( true );

		return $field;
	}

	/**
	 * Get the Sensei Quizzes field.
	 *
	 * @return Fields\Sensei_Quiz
	 */
	public static function get_quizzes_field() {
		$field = new Fields\Sensei_Quiz();
		$field->set_name( 'sensei_quizzes' );
		$field->set_title( __( 'Quizzes', 'automatewoo' ) );
		$field->set_description( __( 'Select quizzes here to have this workflow trigger only for those specific quizzes. Leave blank to run for all quizzes.', 'automatewoo' ) );
		$field->set_multiple( true );

		return $field;
	}

	/**
	 * Get the Sensei Question field.
	 *
	 * @return Fields\Sensei_Question
	 */
	public static function get_question_field() {
		$field = new Fields\Sensei_Question();
		$field->set_name( 'sensei_question' );
		$field->set_title( __( 'Question', 'automatewoo' ) );
		$field->set_description( __( 'Select question here to have this workflow trigger only for those specific question.', 'automatewoo' ) );
		$field->set_required( true );

		return $field;
	}

	/**
	 * Get the Sensei Groups field.
	 *
	 * @return Fields\Sensei_Group
	 */
	public static function get_groups_field() {
		$field = new Fields\Sensei_Group();
		$field->set_name( 'sensei_groups' );
		$field->set_title( __( 'Groups', 'automatewoo' ) );
		$field->set_description( __( 'Select groups here to have this workflow trigger only for those specific groups. Leave blank to run for all groups.', 'automatewoo' ) );
		$field->set_multiple( true );

		return $field;
	}

	/**
	 * Get student courses.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function get_student_course_ids( $user_id ) {
		$course_ids      = [];
		$course_statuses = \Sensei_Utils::sensei_check_for_activity(
			[
				'user_id' => $user_id,
				'type'    => 'sensei_course_status',
				'status'  => 'any',
			],
			true
		);

		// Check for activity returns single if only one. We always want an array.
		if ( ! is_array( $course_statuses ) ) {
			$course_statuses = [ $course_statuses ];
		}

		foreach ( $course_statuses as $status ) {
			$course_ids[] = intval( $status->comment_post_ID );
		}

		return $course_ids;
	}

	/**
	 * Get student quizzes.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function get_student_quiz_ids( $user_id ) {
		$course_ids = self::get_student_course_ids( $user_id );
		if ( empty( $course_ids ) ) {
			return [];
		}

		$quiz_ids = [];
		foreach ( $course_ids as $course_id ) {
			$quizzes = \Sensei()->course->course_quizzes( $course_id );
			if ( ! empty( $quizzes ) ) {
				$quiz_ids = array_merge( $quiz_ids, $quizzes );
			}
		}
		return $quiz_ids;
	}
}
