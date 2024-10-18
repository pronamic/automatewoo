<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Order_Pending
 */
class Trigger_Order_Pending extends Trigger_Abstract_Order_Status_Base {

	/**
	 * Target transition status.
	 *
	 * @var string|false
	 */
	public $target_status = 'pending';


	/**
	 * Method to set title, group, description and other admin props.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Order Pending Payment', 'automatewoo' );
	}
}
