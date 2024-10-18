<?php

namespace AutomateWoo;

use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Triggers\AbstractBatchedDailyTrigger;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Customer_Win_Back
 */
class Trigger_Customer_Win_Back extends AbstractBatchedDailyTrigger {

	/**
	 * @var string[]
	 */
	public $supplied_data_items = [ 'customer', 'order' ];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title        = __( 'Customer Win Back', 'automatewoo' );
		$this->description  = __( "This trigger fires for customers based on the date of their last paid order. The 'order based' variables, rules and actions used by this trigger only refer to the customer's last paid order.", 'automatewoo' );
		$this->description .= ' ' . $this->get_description_text_workflow_not_immediate();
		$this->group        = __( 'Customers', 'automatewoo' );
	}

	/**
	 * Load fields.
	 */
	public function load_fields() {
		$period = ( new Fields\Positive_Number() )
			->set_name( 'days_since_last_purchase' )
			->set_title( __( 'Minimum days since purchase', 'automatewoo' ) )
			->set_description( __( "Defines the minimum number of days to wait after a customer's last purchase.", 'automatewoo' ) )
			->set_required();

		$period_max = ( new Fields\Number() )
			->set_name( 'days_since_last_purchase_max' )
			->set_title( __( 'Maximum days since purchase', 'automatewoo' ) )
			->set_description( __( "Defines the maximum number of days after the customer's last purchase that this trigger will fire. The default value will be 3 greater than the value of the minimum days field.", 'automatewoo' ) );

		$repeat = ( new Fields\Checkbox() )
			->set_name( 'enable_repeats' )
			->set_title( __( 'Enable repeats', 'automatewoo' ) )
			->set_description( __( 'If checked this trigger will repeatedly fire after the minimum last purchase date passes and the customer has not made a purchase. E.g. if the minimum is set to 30 days the trigger will fire 30 days after the customers last purchase and every 30 days from then until the maximum is reached or the customer makes another purchase. If unchecked the trigger will not repeat until the customer makes a new purchase.', 'automatewoo' ) );

		$this->add_field( $period );
		$this->add_field( $period_max );
		$this->add_field( $this->get_field_time_of_day() );
		$this->add_field( $repeat );
	}

	/**
	 * Get a batch of items to process for given workflow.
	 *
	 * @param Workflow $workflow
	 * @param int      $offset The batch query offset.
	 * @param int      $limit  The max items for the query.
	 *
	 * @return array[] Array of items in array format. Items will be stored in the database so they should be IDs not objects.
	 */
	public function get_batch_for_workflow( Workflow $workflow, int $offset, int $limit ): array {
		$tasks = [];

		foreach ( $this->get_customers_matching_last_purchase_range( $workflow, $limit, $offset ) as $customer ) {
			$tasks[] = [
				'customer' => $customer->get_id(),
			];
		}

		return $tasks;
	}

	/**
	 * Process a single item for a workflow to process.
	 *
	 * @param Workflow $workflow
	 * @param array    $item
	 *
	 * @throws InvalidArgument If customer is not set.
	 * @throws RuntimeException If there is an error.
	 */
	public function process_item_for_workflow( Workflow $workflow, array $item ) {
		if ( ! isset( $item['customer'] ) ) {
			throw InvalidArgument::missing_required( 'customer' );
		}

		$customer = Customer_Factory::get( $item['customer'] );
		if ( ! $customer ) {
			throw new RuntimeException( 'Customer was not found.' );
		}

		$last_paid_order = $customer->get_nth_last_paid_order( 1 );
		if ( ! $last_paid_order ) {
			throw new RuntimeException( 'The customer has no paid orders. An order may have been edited or deleted since starting the job.' );
		}

		$workflow->maybe_run(
			[
				'customer' => $customer,
				'order'    => $last_paid_order,
			]
		);
	}

	/**
	 * Fetch users by date using the last order meta field.
	 *
	 * @param Workflow $workflow
	 * @param int      $limit
	 * @param int      $offset
	 *
	 * @return Customer[]
	 */
	protected function get_customers_matching_last_purchase_range( $workflow, $limit, $offset ) {
		$min_date = $this->get_min_last_order_date( $workflow );
		$max_date = $this->get_max_last_order_date( $workflow );

		if ( ! $min_date ) {
			// a minimum date must be set
			return [];
		}

		$query = new Customer_Query();
		$query->set_limit( $limit );
		$query->set_offset( $offset );
		$query->where( 'last_purchased', $min_date, '<' );
		$query->set_ordering( 'last_purchased' );

		if ( $max_date ) {
			$query->where( 'last_purchased', $max_date, '>' );
		}

		return $query->get_results();
	}


	/**
	 * @param Workflow $workflow
	 *
	 * @return DateTime|bool
	 */
	protected function get_min_last_order_date( $workflow ) {
		$days = $workflow->get_trigger_option( 'days_since_last_purchase' );

		if ( ! $days ) {
			return false;
		}

		$days = -1 * (int) $days;
		$date = new DateTime();
		$date->modify( "{$days} days" );

		return $date;
	}


	/**
	 * @param Workflow $workflow
	 *
	 * @return DateTime|bool
	 */
	protected function get_max_last_order_date( $workflow ) {
		$days = $workflow->get_trigger_option( 'days_since_last_purchase_max' );

		if ( ! $days ) {
			// default to 3 greater than the minimum days field
			$days = absint( $workflow->get_trigger_option( 'days_since_last_purchase' ) ) + 3;
		}

		$days = -1 * (int) $days;
		$date = new DateTime();
		$date->modify( "{$days} days" );

		return $date;
	}

	/**
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		$customer          = $workflow->data_layer()->get_customer();
		$most_recent_order = $workflow->data_layer()->get_order();
		$enable_repeats    = $workflow->get_trigger_option( 'enable_repeats' );

		if ( ! $customer || ! $most_recent_order ) {
			return false;
		}

		// exclude customers with active subscriptions
		// these customers are still active but their last purchase date might suggest they are inactive
		// TODO in the future the end date of the customers last subscription should be factored in to this logic
		if ( Integrations::is_subscriptions_active() && $customer->is_registered() ) {
			if ( wcs_user_has_subscription( $customer->get_user_id(), '', 'active' ) ) {
				return false;
			}
		}

		// for accuracy, we use the actual order date instead of Customer::get_date_last_purchased()
		$last_purchase_date  = aw_normalize_date( $most_recent_order->get_date_created() ); // convert to UTC
		$min_last_order_date = $this->get_min_last_order_date( $workflow );

		if ( ! $min_last_order_date || ! $last_purchase_date ) {
			return false;
		}

		// update the stored last purchase date
		$customer->set_date_last_purchased( $last_purchase_date );
		$customer->save();

		// check that the user has not made a purchase since the start of the delay period
		if ( $last_purchase_date->getTimestamp() > $min_last_order_date->getTimestamp() ) {
			return false;
		}

		// if repeats are enabled the wait period should start at the last time the workflow was run or queued
		// if repeats are disabled the date range should start at the last order date
		$wait_period = $enable_repeats ? $min_last_order_date : $last_purchase_date;

		if ( $workflow->get_timing_type() !== 'immediately' ) {
			// check workflow has not been added to the queue already

			$query = new Queue_Query();
			$query->where_workflow( $workflow->get_translation_ids() );
			$query->where_date_created( $wait_period, '>' );
			$query->where_customer_or_legacy_user( $customer );

			if ( $query->has_results() ) {
				return false;
			}
		}

		// check the workflow has not run already
		$log_query = new Log_Query();
		$log_query->where_workflow( $workflow->get_translation_ids() );
		$log_query->where_date( $wait_period, '>' );
		$log_query->where_customer_or_legacy_user( $customer );

		if ( $log_query->has_results() ) {
			return false;
		}

		return true;
	}


	/**
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		$customer = $workflow->data_layer()->get_customer();

		if ( ! $customer ) {
			return false;
		}

		$min_last_order_date = $this->get_min_last_order_date( $workflow );
		$last_purchase_date  = $customer->get_date_last_purchased();

		if ( ! $min_last_order_date || ! $last_purchase_date ) {
			return false;
		}

		// check that the user has not made a purchase while the workflow was queued
		if ( $last_purchase_date->getTimestamp() > $min_last_order_date->getTimestamp() ) {
			return false;
		}

		return true;
	}
}
