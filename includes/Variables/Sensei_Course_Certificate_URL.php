<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Course_Certificate_URL
 *
 * @since 5.6.10
 */
class Variable_Sensei_Course_Certificate_URL extends Variable {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the Course's certificate URL.", 'automatewoo' );
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

		$certificate_url = '';
		$args            = array(
			'post_type'   => 'certificate',
			'author'      => $user->ID,
			'meta_key'    => 'course_id',
			'meta_value'  => $course_id,
			'numberposts' => 1,
		);

		$certificates = get_posts( $args );
		if ( ! empty( $certificates ) ) {
			$certificate_url = get_permalink( $certificates[0] );
		}

		return $certificate_url;
	}
}
