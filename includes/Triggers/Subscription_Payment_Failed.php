<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Trigger_Subscription_Payment_Failed
 */
class Trigger_Subscription_Payment_Failed extends Trigger {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ 'customer', 'subscription', 'order' ];

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var string|array
	 */
	protected $required_async_events = 'subscription_renewal_payment_failed';

	/**
	 * Method to set title, group, description and other admin props
	 */
	public function load_admin_details() {
		$this->title = __( 'Subscription Renewal Payment Failed', 'automatewoo' );
		$this->group = Subscription_Workflow_Helper::get_group_name();
	}

	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {
		$this->add_field( Subscription_Workflow_Helper::get_products_field() );
	}


	/**
	 * Register the hooks for when the trigger should run
	 */
	public function register_hooks() {
		add_action( 'automatewoo/subscription/renewal_payment_failed_async', [ $this, 'handle_payment_failed' ], 10, 2 );
	}


	/**
	 * @param int $subscription_id
	 * @param int $order_id
	 */
	public function handle_payment_failed( $subscription_id, $order_id ) {
		$subscription = wcs_get_subscription( $subscription_id );
		$order        = wc_get_order( $order_id );

		if ( ! $subscription || ! $order ) {
			return;
		}

		$this->maybe_run(
			[
				'subscription' => $subscription,
				'order'        => $order,
				'customer'     => Customer_Factory::get_by_user_id( $subscription->get_user_id() ),
			]
		);
	}


	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {

		$subscription = $workflow->data_layer()->get_subscription();

		if ( ! $subscription ) {
			return false;
		}

		if ( ! Subscription_Workflow_Helper::validate_products_field( $workflow ) ) {
			return false;
		}

		return true;
	}
}
