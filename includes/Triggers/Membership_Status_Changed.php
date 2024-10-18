<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Trigger_Membership_Status_Changed
 * @since 2.8.3
 */
class Trigger_Membership_Status_Changed extends Trigger_Abstract_Memberships {

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var array|string
	 */
	protected $required_async_events = 'membership_status_changed';

	/**
	 * Method to set title, group, description and other admin props
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Membership Status Changed', 'automatewoo' );
	}

	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {

		$statuses = Memberships_Helper::get_membership_statuses();

		$plans_field = $this->get_field_membership_plans();

		$placeholder = __( 'Leave blank for any status', 'automatewoo' );

		$from = ( new Fields\Select() )
			->set_title( __( 'Status changes from', 'automatewoo' ) )
			->set_name( 'membership_status_from' )
			->set_options( $statuses )
			->set_placeholder( $placeholder )
			->set_multiple();

		$to = ( new Fields\Select() )
			->set_title( __( 'Status changes to', 'automatewoo' ) )
			->set_name( 'membership_status_to' )
			->set_options( $statuses )
			->set_placeholder( $placeholder )
			->set_multiple();

		$this->add_field( $plans_field );
		$this->add_field( $from );
		$this->add_field( $to );
	}


	/**
	 * The hooks when this trigger should run.
	 */
	public function register_hooks() {
		add_action( 'automatewoo/membership_status_changed_async', [ $this, 'handle_async_event' ], 10, 3 );
	}


	/**
	 * @param int    $membership_id
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function handle_async_event( $membership_id, $old_status, $new_status ) {
		if ( ! $membership_id ) {
			return;
		}

		$membership = wc_memberships_get_user_membership( $membership_id );

		if ( ! $membership ) {
			return;
		}

		Temporary_Data::set( 'membership_old_status', $membership->get_id(), $old_status );
		Temporary_Data::set( 'membership_new_status', $membership->get_id(), $new_status );

		$this->maybe_run(
			[
				'membership' => $membership,
				'customer'   => Customer_Factory::get_by_user_id( $membership->get_user_id() ),
			]
		);
	}


	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		$membership = $workflow->data_layer()->get_membership();
		if ( ! $membership ) {
			return false;
		}

		$status_from = $workflow->get_trigger_option( 'membership_status_from' );
		$status_to   = $workflow->get_trigger_option( 'membership_status_to' );
		$plans       = $workflow->get_trigger_option( 'membership_plans' );
		$old_status  = Temporary_Data::get( 'membership_old_status', $membership->get_id() );
		$new_status  = Temporary_Data::get( 'membership_new_status', $membership->get_id() );

		if ( ! $this->validate_status_field( $status_from, $old_status ) ) {
			return false;
		}

		if ( ! $this->validate_status_field( $status_to, $new_status ) ) {
			return false;
		}

		if ( ! empty( $plans ) ) {
			if ( ! in_array( $membership->get_plan_id(), array_map( 'intval', $plans ), true ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Ensures 'to' status has not changed while sitting in queue
	 *
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		$membership = $workflow->data_layer()->get_membership();
		$status_to  = $workflow->get_trigger_option( 'membership_status_to' );

		if ( ! $membership ) {
			return false;
		}

		if ( ! $this->validate_status_field( $status_to, $membership->get_status() ) ) {
			return false;
		}

		return true;
	}
}
