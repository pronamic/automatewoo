<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Sensei_Quiz_Passmark
 *
 * @since 5.6.10
 */
class Variable_Sensei_Quiz_Passmark extends Variable {

	/**
	 * Set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( "Displays the quiz's passmark.", 'automatewoo' );
	}

	/**
	 * Get Variable Value.
	 *
	 * @param \WP_Post $quiz       \WP_Post Object
	 * @param array    $parameters Variable parameters
	 * @return string
	 */
	public function get_value( $quiz, $parameters ) {
		return get_post_meta( $quiz->ID, '_quiz_passmark', true );
	}
}
