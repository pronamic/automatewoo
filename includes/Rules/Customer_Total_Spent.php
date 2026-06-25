<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Total_Spent
 */
class Customer_Total_Spent extends Abstract_Number {

	/** @var string */
	public $data_item = DataTypes::CUSTOMER;

	/** @var bool */
	public $support_floats = true;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Customer - Total Spent', 'automatewoo' );
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		return $this->validate_number( $customer->get_total_spent(), $compare, $value );
	}
}
