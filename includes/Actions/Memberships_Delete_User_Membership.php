<?php
namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Memberships_Delete_User_Membership
 * @since 2.9
 */
class Action_Memberships_Delete_User_Membership extends Action_Memberships_Abstract {

	/**
	 * The data items required by the action.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'customer' ];


	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Delete Membership For User', 'automatewoo' );
	}

	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {

		$plans = Memberships_Helper::get_membership_plans();

		$plan = ( new Fields\Select( false ) )
			->set_name( 'plan' )
			->set_title( __( 'Plan', 'automatewoo' ) )
			->set_options( $plans )
			->set_required();

		$this->add_field( $plan );
	}


	/**
	 * Run the action
	 *
	 * @throws \Exception When an error happens.
	 */
	public function run() {

		$customer = $this->workflow->data_layer()->get_customer();
		$plan_id  = absint( $this->get_option( 'plan' ) );

		if ( ! $customer->is_registered() || ! $plan_id ) {
			return;
		}

		$membership = wc_memberships_get_user_membership( $customer->get_user_id(), $plan_id );

		if ( ! $membership ) {
			$this->workflow->log_action_note( $this, __( 'The user did not have membership that matched the selected plan.', 'automatewoo' ) );
			return;
		}

		$membership_id = $membership->get_id();

		$success = wp_delete_post( $membership_id, true );

		if ( $success ) {
			// translators: The Membership ID
			$this->workflow->log_action_note( $this, sprintf( __( 'Deleted membership #%s', 'automatewoo' ), $membership_id ) );
		} else {
			// translators: The Membership ID
			throw new \Exception( esc_textarea( sprintf( __( 'Failed deleting membership #%s', 'automatewoo' ), $membership_id ) ) );
		}
	}
}
