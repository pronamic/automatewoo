<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Sensei_Lesson_Completed.
 *
 * @since 5.6.10
 * @package AutomateWoo
 */
class Trigger_Sensei_Lesson_Completed extends Trigger {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ DataTypes::SENSEI_COURSE, DataTypes::SENSEI_LESSON, DataTypes::SENSEI_TEACHER, DataTypes::CUSTOMER ];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Lesson Completed', 'automatewoo' );
		$this->description = __( 'This trigger fires after the lesson is completed.', 'automatewoo' );
		$this->group       = Sensei_Workflow_Helper::get_group_name();
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'sensei_user_lesson_end', array( $this, 'handle_lesson_completed' ), 9, 2 );
	}

	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {
		$lessons = Sensei_Workflow_Helper::get_lessons_field();
		$this->add_field( $lessons );
	}

	/**
	 * Handle Lesson Completed.
	 *
	 * @param int $user_id   User ID.
	 * @param int $lesson_id Lesson ID.
	 */
	public function handle_lesson_completed( $user_id, $lesson_id ) {
		$lesson = get_post( $lesson_id );
		$user   = get_user_by( 'id', $user_id );
		if ( ! $lesson || ! $user ) {
			return;
		}

		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		$course    = get_post( $course_id );

		if ( ! $course ) {
			return;
		}

		foreach ( $this->get_workflows() as $workflow ) {
			$sensei_lessons = Clean::ids( $workflow->get_trigger_option( 'sensei_lessons' ) );

			if ( ! empty( $sensei_lessons ) && ! in_array( $lesson->ID, $sensei_lessons, true ) ) {
				continue;
			}

			// Prevent duplicate triggers.
			$query = new Queue_Query();
			$query->where_workflow( $workflow->get_id() );
			$query->where_user( $user_id );
			$query->where_lesson( $lesson_id );
			$results = $query->get_results();
			if ( ! empty( $results ) ) {
				continue;
			}

			$workflow->maybe_run(
				[
					DataTypes::SENSEI_COURSE  => $course,
					DataTypes::SENSEI_TEACHER => get_user_by( 'id', $course->post_author ),
					DataTypes::SENSEI_LESSON  => $lesson,
					DataTypes::CUSTOMER       => Customer_Factory::get_by_user_id( $user_id ),
				]
			);
		}
	}
}
