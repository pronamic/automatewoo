<?php

namespace AutomateWoo\DataTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Group data type class.
 *
 * @since 5.6.10
 */
class SenseiGroup extends Post {

	/**
	 * Get singular name for data type.
	 *
	 * @return string
	 */
	public function get_singular_name() {
		return __( 'Group', 'automatewoo' );
	}

	/**
	 * Get plural name for data type.
	 *
	 * @return string
	 */
	public function get_plural_name() {
		return __( 'Groups', 'automatewoo' );
	}
}
