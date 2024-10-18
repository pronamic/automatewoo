<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Memberships_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Active_Membership_Plans
 */
class Customer_Active_Membership_Plans extends Preloaded_Select_Rule_Abstract {

	/**
	 * Define the data type used by the rule.
	 *
	 * @var string
	 */
	public $data_item = DataTypes::CUSTOMER;

	/**
	 * Allow multiple selections?
	 *
	 * @var bool
	 */
	public $is_multi = true;


	/**
	 * Initialize the rule
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Customer - Active Memberships Plans', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	public function load_select_choices() {
		return Memberships_Helper::get_membership_plans();
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param array|string          $value
	 *
	 * @return bool
	 */
	public function validate( $customer, $compare, $value ) {
		$active_plans = [];

		if ( $customer->is_registered() ) {
			foreach ( wc_memberships_get_user_active_memberships( $customer->get_user_id() ) as $membership ) {
				$active_plans[] = $membership->get_plan_id();
			}
		}

		return $this->validate_select( $active_plans, $compare, $value );
	}
}
