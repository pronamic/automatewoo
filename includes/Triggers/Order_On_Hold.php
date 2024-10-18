<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Order_On_Hold
 */
class Trigger_Order_On_Hold extends Trigger_Abstract_Order_Status_Base {

	/**
	 * Target transition status.
	 *
	 * @var string|false
	 */
	public $target_status = 'on-hold';


	/**
	 * Method to set title, group, description and other admin props.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Order On Hold', 'automatewoo' );
	}
}
