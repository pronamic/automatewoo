<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Item_Count
 */
class Order_Item_Count extends Abstract_Number {

	public $data_item = DataTypes::ORDER;

	public $support_floats = false;


	function init() {
		$this->title = __( 'Order - Item Count', 'automatewoo' );
	}


	/**
	 * @param $order \WC_Order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $order, $compare, $value ) {
		return $this->validate_number( $order->get_item_count(), $compare, $value );
	}


}
