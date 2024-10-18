<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Role
 */
class Customer_Role extends Preloaded_Select_Rule_Abstract {

	public $data_item = DataTypes::CUSTOMER;


	function init() {
		parent::init();

		$this->title = __( 'Customer - User Role', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		global $wp_roles;
		$choices = [];

		foreach( $wp_roles->roles as $key => $role ) {
			$choices[$key] = $role['name'];
		}

		$choices['guest'] = __( 'Guest', 'automatewoo' );

		return $choices;
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	function validate( $customer, $compare, $value ) {
		return $this->validate_select( $customer->get_role(), $compare, $value );
	}

}
