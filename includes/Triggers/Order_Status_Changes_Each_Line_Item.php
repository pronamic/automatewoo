<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Order_Status_Changes_Each_Line_Item
 * @since 2.9
 */
class Trigger_Order_Status_Changes_Each_Line_Item extends Trigger_Order_Status_Changes {

	/**
	 * Run trigger for each order line item.
	 *
	 * @var bool
	 */
	public $is_run_for_each_line_item = true;


	/**
	 * Method to set title, group, description and other admin props.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Order Status Changes - Each Line Item', 'automatewoo' );
		$this->description = __( 'This trigger runs for each line item after an order status change occurs. Using this trigger allows access to the specific order item and product data.', 'automatewoo' );
	}
}
