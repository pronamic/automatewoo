<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Item_Quantity
 */
class Variable_Order_Item_Quantity extends Variable {


	function load_admin_details() {
		$this->description = __( 'Can be used to display the quantity of a product line item on an order.', 'automatewoo' );
	}


	/**
	 * @param array|\WC_Order_Item_Product $item
	 * @param $parameters
	 * @return string
	 */
	function get_value( $item, $parameters ) {
		return $item->get_quantity();
	}
}
