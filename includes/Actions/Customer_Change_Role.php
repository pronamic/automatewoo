<?php

namespace AutomateWoo;

use AutomateWoo\Fields\User_Role;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Customer_Change_Role
 */
class Action_Customer_Change_Role extends Action {

	/**
	 * The data items required by the action.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'customer' ];

	/**
	 * Method to set the action's admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Change Role', 'automatewoo' );
		$this->group       = __( 'Customer', 'automatewoo' );
		$this->description = __( 'Please note that if the customer is a guest this action will do nothing.', 'automatewoo' );
	}

	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {
		$user_type = new Fields\User_Role( false, $this->allow_all_roles() );
		$user_type->set_required();

		$this->add_field( $user_type );
	}

	/**
	 * Run the action.
	 *
	 * @throws \Exception When an error occurs.
	 */
	public function run() {
		$customer = $this->workflow->data_layer()->get_customer();
		if ( ! $customer ) {
			return;
		}

		$role = $this->get_option( 'user_type' );
		$user = $customer->get_user();

		if ( ! $this->allow_all_roles() && ( in_array( $role, User_Role::PROTECTED_ROLES, true ) || in_array( $customer->get_role(), User_Role::PROTECTED_ROLES, true ) ) ) {
			return;
		}

		if ( $role && $user ) {
			$user->set_role( $role );
		}
	}

	/**
	 * Check if roles in User_Role::PROTECTED_ROLES should be shown as well.
	 *
	 * @return bool
	 */
	private function allow_all_roles() {
		return (bool) apply_filters( 'automatewoo/change_role/allow_all_roles', false );
	}
}
