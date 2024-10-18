<?php

namespace AutomateWoo\DataTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course data type class.
 *
 * @since 5.6.10
 */
class SenseiCourse extends Post {

	/**
	 * Get singular name for data type.
	 *
	 * @return string
	 */
	public function get_singular_name() {
		return __( 'Course', 'automatewoo' );
	}

	/**
	 * Get plural name for data type.
	 *
	 * @return string
	 */
	public function get_plural_name() {
		return __( 'Courses', 'automatewoo' );
	}
}
