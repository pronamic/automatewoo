<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Item_Quantity
 */
class Order_Item_Quantity extends Abstract_Number {

	/** @var string */
	public $data_item = 'order_item';

	/** @var bool */
	public $support_floats = false;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order Line Item - Quantity', 'automatewoo' );
	}


	/**
	 * @param array|\WC_Order_Item_Product $order_item
	 * @param string                       $compare
	 * @param mixed                        $value
	 * @return bool
	 */
	public function validate( $order_item, $compare, $value ) {
		return $this->validate_number( $order_item->get_quantity(), $compare, $value );
	}
}
