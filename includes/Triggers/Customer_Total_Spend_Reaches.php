<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * This trigger hooks in the the order completed action but will only fire once when a users total spend reaches a certain amount.
 *
 * @class Trigger_Customer_Total_Spend_Reaches
 */
class Trigger_Customer_Total_Spend_Reaches extends Trigger_Abstract_Order_Base {

	public $supplied_data_items = [ 'customer', 'order' ];

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var array|string
	 */
	protected $required_async_events = 'order_status_changed';


	function load_admin_details() {
		$this->title = __( 'Customer Total Spend Reaches', 'automatewoo' );
		$this->description = __( "This trigger checks the customer's total spend each time an order is completed.", 'automatewoo');
		$this->group = __( 'Customers', 'automatewoo' );
	}


	function load_fields() {
		$spend = new Fields\Price();
		$spend->set_name( 'total_spend' );
		$spend->set_title( __( 'Total spend', 'automatewoo' ) );
		$spend->set_description( __( 'Do not add a currency symbol.', 'automatewoo' ) );
		$spend->set_required();

		$this->add_field( $spend );
	}


	/**
	 * Must run after customer totals have been updated
	 */
	function register_hooks() {
		add_action( $this->get_hook_order_status_changed(), [ $this, 'catch_hooks' ], 10, 3 );
	}


	/**
	 * @param $order_id
	 * @param $old_status
	 * @param $new_status
	 */
	function catch_hooks( $order_id, $old_status, $new_status ) {
		if ( $new_status !== 'completed' ) {
			return;
		}

		$this->trigger_for_order( $order_id );
	}


	/**
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	function validate_workflow( $workflow ) {
		$customer = $workflow->data_layer()->get_customer();
		$total_spend = $workflow->get_trigger_option( 'total_spend' );

		if ( ! $customer ) {
			return false;
		}

		if ( ! $total_spend || $customer->get_total_spent() < $total_spend ) {
			return false;
		}

		// Only do this once for each user (for each workflow)
		if ( $workflow->get_run_count_for_customer( $customer ) !== 0 ) {
			return false;
		}

		return true;
	}

}
