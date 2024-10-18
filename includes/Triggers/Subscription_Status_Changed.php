<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Subscription_Status_Changed
 */
class Trigger_Subscription_Status_Changed extends Trigger {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ 'subscription', 'customer' ];

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var array|string
	 */
	protected $required_async_events = 'subscription_status_changed';


	/**
	 * Method to set title, group, description and other admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Subscription Status Changed', 'automatewoo' );
		$this->description = __( 'This trigger fires when a subscription status changes with one exception. When an automatic subscription payment is processed the subscription status is changed to "On-hold" and then immediately back to "Active" if the payment is successful. When this happens this trigger deliberately doesn\'t fire because the status was only changed momentarily while the subscription has essentially remained "Active".', 'automatewoo' );
		$this->group       = Subscription_Workflow_Helper::get_group_name();
	}


	/**
	 * Registers fields used for this trigger.
	 */
	public function load_fields() {

		$from = ( new Fields\Subscription_Status() )
			->set_title( __( 'Status changes from', 'automatewoo' ) )
			->set_name( 'subscription_status_from' )
			->set_description( __( 'Select which subscription status changes will trigger this workflow. Leave blank for any subscription status.', 'automatewoo' ) )
			->set_multiple();

		$to = clone $from;

		$to->set_title( __( 'Status changes to', 'automatewoo' ) )
			->set_name( 'subscription_status_to' );

		$recheck_status = ( new Fields\Checkbox() )
			->set_name( 'validate_order_status_before_queued_run' )
			->set_title( __( 'Recheck status before run', 'automatewoo' ) )
			->set_description( __( "This is useful for workflows that are not run immediately as it ensures the status of the subscription hasn't changed since initial trigger.", 'automatewoo' ) )
			->set_default_to_checked();

		$this->add_field( $from );
		$this->add_field( $to );
		$this->add_field( Subscription_Workflow_Helper::get_products_field() );
		$this->add_field( $recheck_status );
	}


	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( $this->get_hook_subscription_status_changed(), [ $this, 'handle_status_changed' ], 10, 3 );
	}


	/**
	 * @param int    $subscription_id
	 * @param string $new_status
	 * @param string $old_status
	 */
	public function handle_status_changed( $subscription_id, $new_status, $old_status ) {
		// use temp data to store the real status changed, status of sub may have already changed if using async
		Temporary_Data::set( 'subscription_old_status', $subscription_id, $old_status );
		Temporary_Data::set( 'subscription_new_status', $subscription_id, $new_status );
		Subscription_Workflow_Helper::trigger_for_subscription( $this, $subscription_id );
	}


	/**
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		$subscription = $workflow->data_layer()->get_subscription();
		$status_from  = $workflow->get_trigger_option( 'subscription_status_from' );
		$status_to    = $workflow->get_trigger_option( 'subscription_status_to' );

		if ( ! $subscription ) {
			return false;
		}

		$old_status = Temporary_Data::get( 'subscription_old_status', $subscription->get_id() );
		$new_status = Temporary_Data::get( 'subscription_new_status', $subscription->get_id() );

		if ( ! $this->validate_status_field( $status_from, $old_status ) ) {
			return false;
		}

		if ( ! $this->validate_status_field( $status_to, $new_status ) ) {
			return false;
		}

		if ( ! Subscription_Workflow_Helper::validate_products_field( $workflow ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Ensures 'to' status has not changed while sitting in queue.
	 *
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		$subscription = $workflow->data_layer()->get_subscription();

		if ( ! $subscription ) {
			return false;
		}

		// Option to validate order status
		if ( $workflow->get_trigger_option( 'validate_order_status_before_queued_run' ) ) {

			$status_to = $workflow->get_trigger_option( 'subscription_status_to' );

			if ( ! $this->validate_status_field( $status_to, $subscription->get_status() ) ) {
				return false;
			}
		}

		return true;
	}
}
