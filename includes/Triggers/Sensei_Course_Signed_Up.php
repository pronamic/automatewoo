<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Sensei_Course_Signed_Up.
 *
 * @since 5.6.10
 * @package AutomateWoo
 */
class Trigger_Sensei_Course_Signed_Up extends Trigger {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ DataTypes::SENSEI_COURSE, DataTypes::SENSEI_TEACHER, DataTypes::CUSTOMER ];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Signed Up To a Course', 'automatewoo' );
		$this->description = __( 'This trigger fires after a student signs up for a course.', 'automatewoo' );
		$this->group       = Sensei_Workflow_Helper::get_group_name();
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'sensei_user_course_start', array( $this, 'handle_course_signup' ), 10, 2 );
	}

	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {
		$courses = Sensei_Workflow_Helper::get_courses_field();
		$this->add_field( $courses );
	}

	/**
	 * Handle Course Signup.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 */
	public function handle_course_signup( $user_id, $course_id ) {
		$course = get_post( $course_id );
		$user   = get_user_by( 'id', $user_id );
		if ( ! $course || ! $user ) {
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
					DataTypes::CUSTOMER       => Customer_Factory::get_by_user_id( $user_id ),
				]
			);
		}
	}
}
