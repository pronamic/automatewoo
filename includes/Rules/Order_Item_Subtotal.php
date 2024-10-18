<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Item_Subtotal
 *
 * @since 4.9.0
 */
class Order_Item_Subtotal extends Order_Item_Total {

	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order Line Item - Subtotal', 'automatewoo' );
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
		return $this->validate_number( $item->get_subtotal(), $compare, $value );
	}
}
