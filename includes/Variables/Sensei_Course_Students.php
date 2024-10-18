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
class Variable_Sensei_Course_Students extends Variable {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {

		$this->add_parameter_select_field(
			'sort',
			__( 'Set the sorting of the students.', 'automatewoo' ),
			[
				''          => __( 'Default', 'automatewoo' ),
				'date-desc' => __( 'Course started - Descending', 'automatewoo' ),
				'date-asc'  => __( 'Course started - Ascending', 'automatewoo' ),
			]
		);

		$this->add_parameter_text_field( 'limit', __( 'Set the maximum number of students that will be displayed.', 'automatewoo' ), false, 10 );

		$this->description = __( 'Displays the list of course students.', 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_Post $course     \WP_Post Object
	 * @param array    $parameters Variable parameters
	 * @return string
	 */
	public function get_value( $course, $parameters ) {
		$limit = isset( $parameters['limit'] ) ? absint( $parameters['limit'] ) : 10;
		$sort  = isset( $parameters['sort'] ) ? $parameters['sort'] : '';

		$activity_args = array(
			'post_id' => $course->ID,
			'type'    => 'sensei_course_status',
			'status'  => 'any',
			'number'  => $limit,
			'offset'  => 0,
		);

		if ( 'date-desc' === $sort || 'date-asc' === $sort ) {
			$activity_args['orderby'] = 'comment_date';
			$activity_args['order']   = $sort === 'date-desc' ? 'DESC' : 'ASC';
		}

		$learners = \Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( empty( $learners ) ) {
			return '';
		}

		if ( 1 === $limit && $learners instanceof \WP_Comment ) {
			$learners = array( $learners );
		}

		$learners_list = '<ul>';
		foreach ( $learners as $learner ) {
			$user       = get_user_by( 'id', $learner->user_id );
			$user_email = $user->user_email;
			$user_name  = aw_get_full_name( $user );

			$billing_email      = get_user_meta( $learner->user_id, 'billing_email', true );
			$billing_first_name = get_user_meta( $learner->user_id, 'billing_first_name', true );
			$billing_last_name  = get_user_meta( $learner->user_id, 'billing_last_name', true );

			if ( $billing_email ) {
				$user_email = $billing_email;
			}

			if ( $billing_first_name || $billing_last_name ) {
				/* translators: %1$s: first name, %2$s: last name */
				$user_name = trim( sprintf( _x( '%1$s %2$s', 'Student full name', 'automatewoo' ), $billing_first_name, $billing_last_name ) );
			}

			$learners_list .= '<li>' . $user_name . ' &lt;' . $user_email . '&gt;</li>';
		}
		$learners_list .= '</ul>';

		return $learners_list;
	}
}
