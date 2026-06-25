<?php

namespace AutomateWoo\Rules;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Customer_Role
 */
class Customer_Role extends Preloaded_Select_Rule_Abstract {

	/** @var string */
	public $data_item = DataTypes::CUSTOMER;


	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Customer - User Role', 'automatewoo' );
	}


	/**
	 * @return array
	 */
	public function load_select_choices() {
		global $wp_roles;
		$choices = [];

		foreach ( $wp_roles->roles as $key => $role ) {
			$choices[ $key ] = $role['name'];
		}

		$choices['guest'] = __( 'Guest', 'automatewoo' );

		return $choices;
	}


	/**
	 * @param \AutomateWoo\Customer $customer
	 * @param string                $compare
	 * @param mixed                 $value
	 * @return bool
	 *
	 * @since 6.5.0
	 */
	public function validate( $customer, $compare, $value ) {
		if ( $customer->is_registered() ) {
			$user = $customer->get_user();
			if ( $user ) {
				foreach ( $user->roles as $role ) {
					if ( $this->validate_select( $role, 'is', $value ) ) {
						return 'is' === $compare;
					}
				}
				return 'is_not' === $compare;
			}
		}

		return $this->validate_select( 'guest', $compare, $value );
	}
}
