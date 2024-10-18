<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Trigger;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class for manual triggers.
 *
 * @since   5.0.0
 * @package AutomateWoo
 */
abstract class AbstractManual extends Trigger implements ManualInterface {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->group = __( 'Manual', 'automatewoo' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		// No trigger event since this is a manual trigger.
	}
}
