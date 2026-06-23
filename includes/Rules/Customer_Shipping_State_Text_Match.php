<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Shipping_State_Text_Match
 */
class Customer_Shipping_State_Text_Match extends Abstract_String {

	public $data_item = DataTypes::CUSTOMER;


	function init() {
		$this->title = __( 'Customer - Shipping State - Text Match', 'automatewoo' );
	}


	/**
	 * @param $customer \AutomateWoo\Customer
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $customer, $compare, $value ) {
		$state = $this->data_layer()->get_customer_shipping_state();
		$country = $this->data_layer()->get_customer_shipping_country();

		return $this->validate_string( aw_get_state_name( $country, $state ), $compare, $value );
	}

}
