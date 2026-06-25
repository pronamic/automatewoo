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
		$this->add_field( $this->get_skip_after_successful_payment_field() );
	}


	/**
	 * Register the hooks for when the trigger should run
	 */
	public function register_hooks() {
		add_action( 'automatewoo/subscription/renewal_payment_failed_async', [ $this, 'handle_payment_failed' ], 10, 2 );
		add_action( 'woocommerce_subscription_renewal_payment_complete', [ $this, 'maybe_clear_queued_events' ], 30, 2 );
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

		// Covers immediate (no-delay) runs, which don't pass through validate_before_queued_event().
		if ( ! $this->passes_skip_after_successful_payment( $workflow ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate queued events before running them.
	 *
	 * @since 6.6.0
	 *
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		return $this->passes_skip_after_successful_payment( $workflow );
	}

	/**
	 * Check the "skip after successful payment" guard for a workflow.
	 *
	 * Returns true when the workflow is allowed to run: either the option is
	 * disabled, or no successful payment has happened since the failed renewal
	 * order. Shared by both the immediate-run (validate_workflow) and queued-run
	 * (validate_before_queued_event) paths.
	 *
	 * @since 6.6.0
	 *
	 * @param Workflow $workflow
	 * @return bool
	 */
	private function passes_skip_after_successful_payment( $workflow ) {
		if ( ! $workflow->get_trigger_option( 'skip_after_successful_payment' ) ) {
			return true;
		}

		$subscription = $workflow->data_layer()->get_subscription();
		$order        = $workflow->data_layer()->get_order();

		if ( ! $subscription || ! $order ) {
			return false;
		}

		return ! $this->subscription_has_successful_payment_since_order( $subscription, $order );
	}

	/**
	 * Clear opted-in queued events after a successful renewal payment.
	 *
	 * @since 6.6.0
	 *
	 * @param int|\WC_Subscription $subscription The subscription.
	 * @param int|\WC_Order        $order        The renewal order.
	 */
	public function maybe_clear_queued_events( $subscription, $order ) {
		$subscription = wcs_get_subscription( $subscription );

		if ( ! $subscription ) {
			return;
		}

		foreach ( $this->get_workflows() as $workflow ) {
			if ( ! $workflow->get_trigger_option( 'skip_after_successful_payment' ) ) {
				continue;
			}

			$query = new Queue_Query();
			$query->where_workflow( $workflow->get_id() );
			$query->where_subscription( $subscription->get_id() );

			foreach ( $query->get_results() as $queued_event ) {
				$failed_order = $queued_event->get_data_layer()->get_order();

				if (
					$failed_order
					&& $this->subscription_has_successful_payment_since_order( $subscription, $failed_order )
				) {
					$queued_event->delete();
				}
			}
		}
	}

	/**
	 * Get the skip after successful payment field.
	 *
	 * @since 6.6.0
	 *
	 * @return Fields\Checkbox
	 */
	protected function get_skip_after_successful_payment_field() {
		return ( new Fields\Checkbox() )
			->set_name( 'skip_after_successful_payment' )
			->set_title( __( 'Do not run after a successful payment', 'automatewoo' ) )
			->set_description( __( 'Enable to prevent delayed failed-payment workflows from running when the failed renewal order, or a later renewal order for the same subscription, has been paid before the workflow runs.', 'automatewoo' ) );
	}

	/**
	 * Check whether the failed renewal has since been followed by a successful payment.
	 *
	 * @since 6.6.0
	 *
	 * @param \WC_Subscription $subscription The subscription.
	 * @param \WC_Order        $failed_order The failed renewal order.
	 *
	 * @return bool
	 */
	protected function subscription_has_successful_payment_since_order( $subscription, $failed_order ) {
		if ( $failed_order->get_date_paid() ) {
			return true;
		}

		$failed_order_date = $failed_order->get_date_created();

		if ( ! $failed_order_date ) {
			return false;
		}

		$failed_order_timestamp = $failed_order_date->getTimestamp();
		$related_order_ids      = $subscription->get_related_orders( 'ids', 'renewal' );

		foreach ( $related_order_ids as $order_id ) {
			if ( (int) $failed_order->get_id() === (int) $order_id ) {
				continue;
			}

			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			// Only consider renewals from the same or a later billing cycle. An older
			// overdue renewal that happens to be paid later must not clear the workflow.
			$order_date = $order->get_date_created();

			if ( ! $order_date || $order_date->getTimestamp() < $failed_order_timestamp ) {
				continue;
			}

			if ( $order->get_date_paid() ) {
				return true;
			}
		}

		return false;
	}
}
