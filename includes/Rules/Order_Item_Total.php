<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Item_Total
 *
 * @since 4.9.0
 */
class Order_Item_Total extends Abstract_Number {

	/**
	 * Data item the rule uses.
	 *
	 * @var string
	 */
	public $data_item = 'order_item';

	/**
	 * Support float values.
	 *
	 * @var bool
	 */
	public $support_floats = true;

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order Line Item - Total', 'automatewoo' );
	}

	/**
	 * Validate the rule.
	 *
	 * @param \WC_Order_Item_Product $item
	 * @param string                 $compare
	 * @param string                 $value
	 *
	 * @return bool
	 */
	public function validate( $item, $compare, $value ) {
		return $this->validate_number( $item->get_total(), $compare, $value );
	}
}
