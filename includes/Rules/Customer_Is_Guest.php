<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Is_Guest
 */
class Customer_Is_Guest extends Abstract_Bool {

	/**
	 * @var string
	 */
	public $data_item = DataTypes::CUSTOMER;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Customer - Is Guest', 'automatewoo' );
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		$is_guest = ! $customer->is_registered();

		switch ( $value ) {
			case 'yes':
				return $is_guest;
			case 'no':
				return ! $is_guest;
		}
	}
}
