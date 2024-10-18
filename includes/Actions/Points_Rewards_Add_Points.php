<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Action_Points_Rewards_Add_Points
 *
 * Increases customer's points.
 *
 * @since   4.5.0
 * @package AutomateWoo
 */
class Action_Points_Rewards_Add_Points extends Action_Points_Rewards_Edit_Points_Abstract {

	/**
	 * Load admin description.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Add Points', 'automatewoo' );
	}

	/**
	 * Run
	 */
	public function run() {
		parent::modify_points( 'add' );
	}
}
