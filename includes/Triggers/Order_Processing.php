<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Order_Processing
 */
class Trigger_Order_Processing extends Trigger_Abstract_Order_Status_Base {

	/**
	 * Target transition status.
	 *
	 * @var string|false
	 */
	public $target_status = 'processing';


	/**
	 * Method to set title, group, description and other admin props.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Order Processing', 'automatewoo' );
	}
}
