<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Sensei_Quiz_Completed.
 *
 * @since 5.6.10
 * @package AutomateWoo
 */
class Trigger_Sensei_Quiz_Completed extends Trigger {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [
		DataTypes::SENSEI_COURSE,
		DataTypes::SENSEI_LESSON,
		DataTypes::SENSEI_QUIZ,
		DataTypes::SENSEI_TEACHER,
		DataTypes::CUSTOMER,
	];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Quiz Completed', 'automatewoo' );
		$this->description = __( 'This trigger fires after the quiz is completed.', 'automatewoo' );
		$this->group       = Sensei_Workflow_Helper::get_group_name();
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'sensei_user_quiz_grade', array( $this, 'handle_quiz_grade' ), 10, 4 );
	}

	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {
		$quizzes = Sensei_Workflow_Helper::get_quizzes_field();
		$this->add_field( $quizzes );
	}

	/**
	 * Handle Quiz Grade.
	 *
	 * @param int $user_id  User ID.
	 * @param int $quiz_id  Quiz ID.
	 * @param int $grade    Grade.
	 * @param int $passmark Passmark.
	 */
	public function handle_quiz_grade( $user_id, $quiz_id, $grade, $passmark ) {
		$quiz = get_post( $quiz_id );
		$user = get_user_by( 'id', $user_id );
		if ( ! $quiz || ! $user || ! function_exists( 'Sensei' ) ) {
			return;
		}

		// Skip trigger for "Quiz Failed", if the quiz not failed.
		if ( 'sensei_quiz_failed' === $this->get_name() && $grade >= $passmark ) {
			return;
		}

		// Skip trigger for "Quiz Passed", if the quiz not passed.
		if ( 'sensei_quiz_passed' === $this->get_name() && $grade < $passmark ) {
			return;
		}

		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		$lesson    = get_post( $lesson_id );
		$course    = get_post( $course_id );

		if ( ! $course || ! $lesson ) {
			return;
		}

		foreach ( $this->get_workflows() as $workflow ) {
			$sensei_quizzes = Clean::ids( $workflow->get_trigger_option( 'sensei_quizzes' ) );

			if ( ! empty( $sensei_quizzes ) && ! in_array( $quiz->ID, $sensei_quizzes, true ) ) {
				continue;
			}

			// Prevent duplicate triggers.
			$query = new Queue_Query();
			$query->where_workflow( $workflow->get_id() );
			$query->where_user( $user_id );
			$query->where_quiz( $quiz_id );
			$results = $query->get_results();
			if ( ! empty( $results ) ) {
				continue;
			}

			$workflow->maybe_run(
				[
					DataTypes::SENSEI_COURSE  => $course,
					DataTypes::SENSEI_TEACHER => get_user_by( 'id', $course->post_author ),
					DataTypes::SENSEI_LESSON  => $lesson,
					DataTypes::SENSEI_QUIZ    => $quiz,
					DataTypes::CUSTOMER       => Customer_Factory::get_by_user_id( $user_id ),
				]
			);
		}
	}
}
