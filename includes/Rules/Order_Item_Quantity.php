<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Item_Quantity
 */
class Order_Item_Quantity extends Abstract_Number {

	public $data_item = 'order_item';

	public $support_floats = false;


	function init() {
		$this->title = __( 'Order Line Item - Quantity', 'automatewoo' );
	}


	/**
	 * @param $order_item array|\WC_Order_Item_Product
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order_item, $compare, $value ) {
		return $this->validate_number( $order_item->get_quantity(), $compare, $value );
	}

}
