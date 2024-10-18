<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Order_Completed
 */
class Trigger_Order_Completed extends Trigger_Abstract_Order_Status_Base {

	/**
	 * Target transition status.
	 *
	 * @var string|false
	 */
	public $target_status = 'completed';


	/**
	 * Method to set title, group, description and other admin props.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Order Completed', 'automatewoo' );
	}
}
