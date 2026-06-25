<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Order_Count
 */
class Customer_Order_Count extends Abstract_Number {

	/** @var string */
	public $data_item = DataTypes::CUSTOMER;

	/** @var bool */
	public $support_floats = false;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Customer - Order Count', 'automatewoo' );
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		return $this->validate_number( $customer->get_order_count(), $compare, $value );
	}
}
