<?php

namespace AutomateWoo;

use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Traits\IntegerValidator;
use AutomateWoo\Triggers\AbstractBatchedDailyTrigger;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Subscription_Before_Renewal
 * @since 2.6.2
 */
class Trigger_Subscription_Before_Renewal extends AbstractBatchedDailyTrigger {

	use IntegerValidator;

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ 'customer', 'subscription' ];

	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		$this->title        = __( 'Subscription Before Renewal', 'automatewoo' );
		$this->description  = __( "This trigger runs once per day for any subscriptions that are due for renewal on the workflow's target date. For example, if set to run 7 days before renewal, it would look for subscriptions that are due for renewal on the date exactly 7 days from now.", 'automatewoo' );
		$this->description .= ' ' . $this->get_description_text_workflow_not_immediate();
		$this->group        = Subscription_Workflow_Helper::get_group_name();
	}

	/**
	 * Load fields.
	 */
	public function load_fields() {

		$days_before_renewal = ( new Fields\Positive_Number() )
			->set_name( 'days_before_renewal' )
			->set_title( __( 'Days before renewal', 'automatewoo' ) )
			->set_required();

		$this->add_field( $days_before_renewal );
		$this->add_field( $this->get_field_time_of_day() );
		$this->add_field( Subscription_Workflow_Helper::get_products_field() );
	}

	/**
	 * Get a batch of items to process for given workflow.
	 *
	 * @param Workflow $workflow
	 * @param int      $offset The batch query offset.
	 * @param int      $limit  The max items for the query.
	 *
	 * @return array[] Array of items in array format. Items will be stored in the database so they should be IDs not objects.
	 *
	 * @throws InvalidArgument If workflow 'days before' option is not valid.
	 */
	public function get_batch_for_workflow( Workflow $workflow, int $offset, int $limit ): array {
		$items = [];

		foreach ( $this->get_subscriptions_for_workflow( $workflow, $offset, $limit ) as $subscription_id ) {
			$items[] = [
				'subscription' => $subscription_id,
			];
		}

		return $items;
	}

	/**
	 * Process a single item for a workflow to process.
	 *
	 * @param Workflow $workflow
	 * @param array    $item
	 */
	public function process_item_for_workflow( Workflow $workflow, array $item ) {
		$subscription = isset( $item['subscription'] ) ? wcs_get_subscription( Clean::id( $item['subscription'] ) ) : false;
		if ( ! $subscription ) {
			return;
		}

		$workflow->maybe_run(
			[
				'subscription' => $subscription,
				'customer'     => Customer_Factory::get_by_user_id( $subscription->get_user_id() ),
			]
		);
	}

	/**
	 * Get subscriptions that match the workflow's date params.
	 *
	 * @param Workflow $workflow
	 * @param int      $offset
	 * @param int      $limit
	 *
	 * @return int[] Array of subscription IDs.
	 *
	 * @throws InvalidArgument If workflow 'days before' option is not valid.
	 */
	protected function get_subscriptions_for_workflow( Workflow $workflow, int $offset, int $limit ) {
		$days_before_renewal = (int) $workflow->get_trigger_option( 'days_before_renewal' );
		$this->validate_positive_integer( $days_before_renewal );

		$date = ( new DateTime() )->add( new \DateInterval( "P{$days_before_renewal}D" ) );

		return $this->query_subscriptions_for_day( $date, '_schedule_next_payment', [ 'wc-active' ], $offset, $limit );
	}

	/**
	 * Query subscriptions for a specific day.
	 *
	 * @param DateTime $date          The target date in UTC timezone.
	 * @param string   $date_meta_key The subscription date meta key to query.
	 * @param array    $statuses      The subscription statues to query.
	 * @param int      $offset
	 * @param int      $limit
	 *
	 * @return int[] Array of subscription IDs.
	 */
	protected function query_subscriptions_for_day( DateTime $date, string $date_meta_key, array $statuses, int $offset, int $limit ) {
		$date->convert_to_site_time();
		$day_start = clone $date;
		$day_end   = clone $date;
		$day_start->set_time_to_day_start();
		$day_end->set_time_to_day_end();
		$day_start->convert_to_utc_time();
		$day_end->convert_to_utc_time();

		if ( function_exists( 'wcs_get_orders_with_meta_query' ) ) {
			return wcs_get_orders_with_meta_query(
				[
					'type'          => 'shop_subscription',
					'status'        => $statuses,
					'return'        => 'ids',
					'limit'         => $limit,
					'offset'        => $offset,
					'no_found_rows' => true,
					'meta_query'    => [
						[
							'key'     => $date_meta_key,
							'compare' => 'BETWEEN',
							'value'   => [ $day_start->to_mysql_string(), $day_end->to_mysql_string() ],
						],
					],
				]
			);
		}

		// Fallback for querying subscriptions before HPOS compatibility was added.
		$query = new WP_Query(
			[
				'post_type'      => 'shop_subscription',
				'post_status'    => $statuses,
				'fields'         => 'ids',
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'no_found_rows'  => true,
				'meta_query'     => [
					[
						'key'     => $date_meta_key,
						'compare' => '>=',
						'value'   => $day_start->to_mysql_string(),
					],
					[
						'key'     => $date_meta_key,
						'compare' => '<=',
						'value'   => $day_end->to_mysql_string(),
					],
				],
			]
		);

		return $query->posts;
	}


	/**
	 * Handle workflow validation.
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

		// ensure that the workflow has not triggered for this subscription in the last 24  hours
		// this avoids duplication that could arise from timezone/DST changes
		if ( $workflow->has_run_for_data_item( 'subscription', DAY_IN_SECONDS ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Validate before a queued workflow event.
	 *
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		$subscription = $workflow->data_layer()->get_subscription();

		if ( ! $subscription ) {
			return false;
		}

		// only trigger for active subscriptions
		if ( ! $subscription->has_status( 'active' ) ) {
			return false;
		}

		return true;
	}
}
