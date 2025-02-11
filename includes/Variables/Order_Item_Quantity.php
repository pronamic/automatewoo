<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_Item_Quantity
 */
class Variable_Order_Item_Quantity extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Can be used to display the quantity of a product line item on an order.', 'automatewoo' );
	}


	/**
	 * @param array|\WC_Order_Item_Product $item
	 * @param array                        $parameters
	 * @return string
	 */
	public function get_value( $item, $parameters ) {
		return $item->get_quantity();
	}
}
