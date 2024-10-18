<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Quiz_Grade
 *
 * @since 5.6.10
 */
class Variable_Sensei_Quiz_Grade extends Variable {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the quiz's grade.", 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_Post $quiz       \WP_Post Object
	 * @param array    $parameters Variable parameters
	 * @param Workflow $workflow
	 * @return string
	 */
	public function get_value( $quiz, $parameters, $workflow ) {
		if ( ! class_exists( '\Sensei_Quiz' ) ) {
			return '';
		}

		$lesson = $workflow->data_layer()->get_lesson();
		$user   = $workflow->data_layer()->get_user();

		if ( ! $lesson || ! $user ) {
			return '';
		}

		$lesson_status = \Sensei_Utils::user_lesson_status( $lesson->ID, $user->ID );

		return get_comment_meta( $lesson_status->comment_ID, 'grade', true ) ?: 0;
	}
}
