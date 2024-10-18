<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Has_Active_Subscription
 */
class Customer_Has_Active_Subscription extends Abstract_Bool {

	public $data_item = DataTypes::CUSTOMER;


	function init() {
		$this->title = __( 'Customer - Has Active Subscription', 'automatewoo' );
	}


	/**
	 * @param $customer \AutomateWoo\Customer
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $customer, $compare, $value ) {
		$is_subscriber = $customer->get_user_id() && wcs_user_has_subscription( $customer->get_user_id(), '', 'active' );

		switch ( $value ) {
			case 'yes':
				return $is_subscriber;
				break;
			case 'no':
				return ! $is_subscriber;
				break;
		}
	}

}
