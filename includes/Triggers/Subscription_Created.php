<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Subscription_Created.
 *
 * @since 3.0
 * @package AutomateWoo
 */
class Trigger_Subscription_Created extends Trigger {

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
	 * @var string|array
	 */
	protected $required_async_events = 'subscription_created';

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Subscription Created', 'automatewoo' );
		$this->description = __( 'This trigger fires after a subscription is created which happens before payment is confirmed. To create a workflow that runs when the subscription is paid, use the Subscription Status Changed trigger.', 'automatewoo' );
		$this->group       = Subscription_Workflow_Helper::get_group_name();
	}

	/**
	 * Load fields.
	 */
	public function load_fields() {
		$this->add_field( Subscription_Workflow_Helper::get_products_field() );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'automatewoo/async/subscription_created', [ $this, 'handle_subscription_created' ] );
	}

	/**
	 * Handle subscription created event.
	 *
	 * @param int $subscription_id
	 */
	public function handle_subscription_created( $subscription_id ) {
		Subscription_Workflow_Helper::trigger_for_subscription( $this, $subscription_id );
	}

	/**
	 * Validate a workflow.
	 *
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
