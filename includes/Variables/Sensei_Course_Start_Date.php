<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Course_Start_Date
 *
 * @since 5.6.10
 */
class Variable_Sensei_Course_Start_Date extends Variable_Abstract_Datetime {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the date when student has started the course.', 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_Post $course     \WP_Post Object
	 * @param array    $parameters Variable parameters
	 * @param Workflow $workflow   Workflow object
	 *
	 * @return string
	 */
	public function get_value( $course, $parameters, $workflow ) {
		$user      = $workflow->data_layer()->get_user();
		$course_id = $course->ID;

		if ( ! $user || ! $course_id ) {
			return '';
		}

		if ( ! class_exists( '\Sensei_Utils' ) ) {
			return '';
		}
		$user_course_status = \Sensei_Utils::user_course_status( $course_id, $user->ID );
		$start              = get_comment_meta( $user_course_status->comment_ID, 'start', true );

		if ( ! $start ) {
			return '';
		}

		return $this->format_datetime( $start, $parameters );
	}
}
