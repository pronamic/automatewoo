<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Sensei_Specific_Answer_Selected.
 *
 * @since 5.6.10
 * @package AutomateWoo
 */
class Trigger_Sensei_Specific_Answer_Selected extends Trigger {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [
		DataTypes::SENSEI_QUIZ,
		DataTypes::SENSEI_LESSON,
		DataTypes::SENSEI_COURSE,
		DataTypes::SENSEI_TEACHER,
		DataTypes::CUSTOMER,
	];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Specific Answer Selected', 'automatewoo' );
		$this->description = __( 'This trigger fires after selecting a specific answer.', 'automatewoo' );
		$this->group       = Sensei_Workflow_Helper::get_group_name();
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'sensei_user_quiz_submitted', array( $this, 'handle_user_quiz_submitted' ), 10, 2 );
	}

	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {
		$sensei_question = Sensei_Workflow_Helper::get_question_field();
		$sensei_answer   = new Fields\Text();
		$sensei_answer->set_name( 'sensei_answer' );
		$sensei_answer->set_title( __( 'Answer', 'automatewoo' ) );
		$sensei_answer->set_description( __( 'Enter answer here for the selected question to match it with user selected answer.', 'automatewoo' ) );
		$sensei_answer->set_required( true );

		$this->add_field( $sensei_question );
		$this->add_field( $sensei_answer );
	}

	/**
	 * Handle User Quiz Submission.
	 * Check submitted answers with the answer set in the trigger.
	 *
	 * @param int $user_id
	 * @param int $quiz_id
	 */
	public function handle_user_quiz_submitted( $user_id, $quiz_id ) {
		$user = get_user_by( 'id', $user_id );
		$quiz = get_post( $quiz_id );
		if ( ! $user || ! $quiz ) {
			return;
		}

		$lesson_id = Sensei()->quiz->get_lesson_id( $quiz_id );
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		$lesson    = get_post( $lesson_id );
		$course    = get_post( $course_id );
		$answers   = Sensei()->quiz->get_user_answers( $lesson_id, $user_id );
		if ( empty( $answers ) || ! $lesson || ! $course ) {
			return;
		}

		foreach ( $this->get_workflows() as $workflow ) {
			$sensei_question = Clean::id( $workflow->get_trigger_option( 'sensei_question' ) );
			$sensei_answer   = Clean::string( $workflow->get_trigger_option( 'sensei_answer' ) );

			// Check if Question and answer details are there in workflow.
			if ( empty( $sensei_question ) || empty( $sensei_answer ) || ! isset( $answers[ $sensei_question ] ) ) {
				continue;
			}

			$posted_answer = $answers[ $sensei_question ];
			if ( is_array( $posted_answer ) ) {
				$posted_answer = current( $posted_answer );
			}

			// Check if posted answer is match with answer entered in workflow.
			if ( $sensei_answer !== $posted_answer ) {
				continue;
			}

			$workflow->maybe_run(
				[
					DataTypes::SENSEI_QUIZ    => $quiz,
					DataTypes::SENSEI_LESSON  => $lesson,
					DataTypes::SENSEI_COURSE  => $course,
					DataTypes::SENSEI_TEACHER => get_user_by( 'id', $course->post_author ),
					DataTypes::CUSTOMER       => Customer_Factory::get_by_user_id( $user_id ),
				]
			);
		}
	}
}
