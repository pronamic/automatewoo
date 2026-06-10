<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Trigger for when a subscription item is switched (upgraded, downgraded, or crossgraded).
 *
 * @since 6.4.0
 * @package AutomateWoo
 */
class Trigger_Subscription_Switched extends Trigger {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ 'subscription', 'customer', 'order' ];

	/**
	 * Async events required by the trigger.
	 *
	 * @since 6.4.0
	 * @var string|array
	 */
	protected $required_async_events = 'subscription_switched';

	/**
	 * Method to set title, group, and description.
	 */
	public function load_admin_details() {
		$this->title = __( 'Subscription Switched', 'automatewoo' );
		$this->group = Subscription_Workflow_Helper::get_group_name();
	}

	/**
	 * Registers fields used for this trigger.
	 */
	public function load_fields() {
		$switch_type = ( new Fields\Select() )
			->set_name( 'switch_type' )
			->set_title( __( 'Switch type', 'automatewoo' ) )
			->set_description(
				__( 'Optionally filter by switch direction. Leave blank to run for any switch type.', 'automatewoo' )
				. '<br><br>'
				. __( '<strong>Important:</strong> Switch direction is based on the cost per day of the new plan compared to the old plan — not the total subscription price or billing period.', 'automatewoo' )
				. '<br><br>'
				. '<strong>' . __( 'Upgrade', 'automatewoo' ) . '</strong> &mdash; ' . __( 'new plan costs more per day.', 'automatewoo' )
				. '<br>'
				. '<strong>' . __( 'Downgrade', 'automatewoo' ) . '</strong> &mdash; ' . __( 'new plan costs less per day.', 'automatewoo' )
				. '<br>'
				. '<strong>' . __( 'Crossgrade', 'automatewoo' ) . '</strong> &mdash; ' . __( 'same cost per day.', 'automatewoo' )
				. '<br><br>'
				. __( 'For example, switching from a monthly plan to a lower-priced yearly plan is a <em>downgrade</em>, even if the merchant earns more revenue annually.', 'automatewoo' )
			)
			->set_options(
				[
					'upgrade'    => __( 'Upgrade', 'automatewoo' ),
					'downgrade'  => __( 'Downgrade', 'automatewoo' ),
					'crossgrade' => __( 'Crossgrade', 'automatewoo' ),
				]
			);

		$this->add_field( $switch_type );
		$this->add_field( Subscription_Workflow_Helper::get_products_field() );
		$this->add_field( Subscription_Workflow_Helper::get_active_subscriptions_only_field() );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'automatewoo/subscription/switched_async', [ $this, 'handle_subscription_switched' ], 10, 3 );
	}

	/**
	 * Handle subscription switched async event.
	 *
	 * @param int    $subscription_id
	 * @param int    $order_id
	 * @param string $switch_direction
	 */
	public function handle_subscription_switched( $subscription_id, $order_id, $switch_direction ) {
		Temporary_Data::set( 'subscription_switch_direction', $subscription_id, $switch_direction );

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

		$switch_type_filter = $workflow->get_trigger_option( 'switch_type' );

		if ( $switch_type_filter ) {
			$actual_direction = Temporary_Data::get( 'subscription_switch_direction', $subscription->get_id() );
			if ( $actual_direction !== $switch_type_filter ) {
				return false;
			}
		}

		if ( ! Subscription_Workflow_Helper::validate_products_field( $workflow ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		$subscription = $workflow->data_layer()->get_subscription();

		if ( ! $subscription ) {
			return false;
		}

		if ( ! Subscription_Workflow_Helper::validate_active_subscriptions_only_field( $workflow ) ) {
			return false;
		}

		return true;
	}
}
