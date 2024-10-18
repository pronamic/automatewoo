<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Order_Count
 */
class Customer_Order_Count extends Abstract_Number {

	public $data_item = DataTypes::CUSTOMER;

	public $support_floats = false;


	function init() {
		$this->title = __( 'Customer - Order Count', 'automatewoo' );
	}


	/**
	 * @param $customer \AutomateWoo\Customer
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $customer, $compare, $value ) {
		return $this->validate_number( $customer->get_order_count(), $compare, $value );
	}

}
