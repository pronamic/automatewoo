<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable_Abstract_Meta class.
 */
abstract class Variable_Abstract_Meta extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->add_parameter_text_field( 'key', __( 'The meta_key of the field you would like to display.', 'automatewoo' ), true );
	}
}
