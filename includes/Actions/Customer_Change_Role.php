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

		if ( ! $role || ! $user ) {
			return;
		}

		if ( ! get_role( $role ) ) {
			/* translators: %s: role key */
			throw new \Exception( esc_html( sprintf( __( 'The role “%s” does not exist.', 'automatewoo' ), $role ) ) );
		}

		if ( ! $this->allow_all_roles() ) {
			if ( User_Role::is_protected_role( $role ) ) {
				/* translators: %s: role key */
				throw new \Exception( esc_html( sprintf( __( 'The role “%s” can’t be assigned by this action. Use the automatewoo/change_role/allow_all_roles filter to allow it.', 'automatewoo' ), $role ) ) );
			}

			if ( $this->is_protected_user( $user ) ) {
				throw new \Exception( esc_html__( 'This customer’s role can’t be changed by this action. Use the automatewoo/change_role/allow_all_roles filter to allow it.', 'automatewoo' ) );
			}
		}

		$user->set_role( $role );
	}

	/**
	 * Check if the user has a protected role or a protected capability.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	private function is_protected_user( $user ) {
		foreach ( $user->roles as $role ) {
			if ( User_Role::is_protected_role( $role ) ) {
				return true;
			}
		}

		foreach ( User_Role::get_protected_capabilities() as $capability ) {
			if ( user_can( $user, $capability ) ) {
				return true;
			}
		}

		return false;
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
