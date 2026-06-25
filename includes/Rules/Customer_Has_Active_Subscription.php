<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Has_Active_Subscription
 */
class Customer_Has_Active_Subscription extends Abstract_Bool {

	/**
	 * @var string
	 */
	public $data_item = DataTypes::CUSTOMER;


	/**
	 * Init the rule.
	 */
	public function init() {
		$this->title = __( 'Customer - Has Active Subscription', 'automatewoo' );
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		$is_subscriber = $customer->get_user_id() && wcs_user_has_subscription( $customer->get_user_id(), '', 'active' );

		switch ( $value ) {
			case 'yes':
				return $is_subscriber;
			case 'no':
				return ! $is_subscriber;
		}
	}
}
