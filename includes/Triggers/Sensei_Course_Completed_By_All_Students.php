<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Sensei_Course_Completed_By_All_Students.
 *
 * @since 5.6.10
 * @package AutomateWoo
 */
class Trigger_Sensei_Course_Completed_By_All_Students extends Trigger {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ DataTypes::SENSEI_COURSE, DataTypes::SENSEI_TEACHER ];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Course Completed by All Students', 'automatewoo' );
		$this->description = __( 'This trigger fires after the course has been completed by all enrolled students. A minimum of 2 students is required to fire this trigger.', 'automatewoo' );
		$this->group       = Sensei_Workflow_Helper::get_group_name();
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'sensei_user_course_end', array( $this, 'handle_course_completed' ), 10, 2 );
	}

	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {
		$courses = Sensei_Workflow_Helper::get_courses_field();
		$this->add_field( $courses );
	}

	/**
	 * Handle Course Completed.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 */
	public function handle_course_completed( $user_id, $course_id ) {
		$course = get_post( $course_id );
		if ( ! $course ) {
			return;
		}

		foreach ( $this->get_workflows() as $workflow ) {
			$sensei_courses = Clean::ids( $workflow->get_trigger_option( 'sensei_courses' ) );

			if ( ! empty( $sensei_courses ) && ! in_array( $course->ID, $sensei_courses, true ) ) {
				continue;
			}

			$workflow->maybe_run(
				[
					DataTypes::SENSEI_COURSE  => $course,
					DataTypes::SENSEI_TEACHER => get_user_by( 'id', $course->post_author ),
				]
			);
		}
	}

	/**
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		$course = $workflow->data_layer()->get_course();
		if ( ! $course ) {
			return false;
		}

		// Only do this once for each course (for each workflow)
		if ( $workflow->get_run_count_for_course( $course ) !== 0 ) {
			return false;
		}

		$args = array(
			'type'    => 'sensei_course_status',
			'status'  => 'any',
			'post_id' => $course->ID,
		);

		$enrolled_count  = \Sensei_Utils::sensei_check_for_activity( $args );
		$completed_count = \Sensei_Utils::sensei_check_for_activity( array_merge( $args, array( 'status' => 'complete' ) ) );

		// Bail if there are not enough students enrolled OR course is not completed by all enrolled students.
		if ( $enrolled_count < 2 || ( $enrolled_count - $completed_count > 0 ) ) {
			return false;
		}

		return true;
	}
}
