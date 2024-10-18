<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Sensei_Quiz_Passed.
 *
 * @since 5.6.10
 * @package AutomateWoo
 */
class Trigger_Sensei_Quiz_Passed extends Trigger_Sensei_Quiz_Completed {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Quiz Passed', 'automatewoo' );
		$this->description = __( 'This trigger fires after passing a quiz.', 'automatewoo' );
	}
}
