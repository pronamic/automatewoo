<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Sensei_Quiz_Failed.
 *
 * @since 5.6.10
 * @package AutomateWoo
 */
class Trigger_Sensei_Quiz_Failed extends Trigger_Sensei_Quiz_Completed {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Quiz Failed', 'automatewoo' );
		$this->description = __( 'This trigger fires after failing a quiz.', 'automatewoo' );
	}
}
