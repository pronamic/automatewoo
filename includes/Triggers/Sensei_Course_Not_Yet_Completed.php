<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Sensei_Course_Not_Yet_Completed.
 *
 * @since 5.6.10
 * @package AutomateWoo
 */
class Trigger_Sensei_Course_Not_Yet_Completed extends Trigger {

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
		$this->title       = __( 'Course Not Yet Completed', 'automatewoo' );
		$this->description = __( 'This trigger will fire if a course has not yet been completed by students after a set period of time. Please carefully choose the timing options that best suit your needs.', 'automatewoo' );
		$this->group       = Sensei_Workflow_Helper::get_group_name();
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'sensei_user_course_start', array( $this, 'queue_course_event' ), 10, 2 );
		add_action( 'sensei_user_course_end', array( $this, 'maybe_clear_queued_events' ), 10, 2 );
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
	public function queue_course_event( $user_id, $course_id ) {
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

	/**
	 * Maybe clear queued events.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 */
	public function maybe_clear_queued_events( $user_id, $course_id ) {
		if ( ! $user_id || ! $course_id ) {
			return;
		}

		$query = new Queue_Query();
		$query->where_workflow( $this->get_workflow_ids() );
		$query->where_user( $user_id );
		$query->where_course( $course_id );

		foreach ( $query->get_results() as $event ) {
			$event->delete();
		}
	}

	/**
	 * Ensures course is not completed by user while sitting in queue
	 * We are already clearing queued events on course completion, but this is an extra check
	 * To ensure the course is not completed before the event is run
	 *
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		if ( ! class_exists( '\Sensei_Utils' ) ) {
			return '';
		}

		$course = $workflow->data_layer()->get_course();
		$user   = $workflow->data_layer()->get_user();

		if ( ! $course || ! $user ) {
			return false;
		}

		// Check if course is completed.
		$user_course_status = \Sensei_Utils::user_course_status( $course->ID, $user->ID );
		$completed_course   = \Sensei_Utils::user_completed_course( $user_course_status );
		if ( $completed_course ) {
			return false;
		}

		return true;
	}
}
