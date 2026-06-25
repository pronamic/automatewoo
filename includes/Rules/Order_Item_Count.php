<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Item_Count
 */
class Order_Item_Count extends Abstract_Number {

	/** @var string */
	public $data_item = DataTypes::ORDER;

	/** @var bool */
	public $support_floats = false;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Order - Item Count', 'automatewoo' );
	}


	/**
	 * @param \WC_Order $order
	 * @param string    $compare
	 * @param mixed     $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {
		return $this->validate_number( $order->get_item_count(), $compare, $value );
	}
}
