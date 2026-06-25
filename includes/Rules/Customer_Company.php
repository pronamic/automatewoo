<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Company
 */
class Customer_Company extends Abstract_String {

	/** @var string */
	public $data_item = DataTypes::CUSTOMER;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Customer - Company', 'automatewoo' );
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		return $this->validate_string( $this->data_layer()->get_customer_company(), $compare, $value );
	}
}
