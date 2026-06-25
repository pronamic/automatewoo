<?php

namespace AutomateWoo\Tools;

use AutomateWoo\Clean;
use AutomateWoo\Customer_Factory;
use AutomateWoo\Customer_Query;
use AutomateWoo\Tool_Background_Processed_Abstract;

defined( 'ABSPATH' ) || exit;

/**
 * Rebuilds each customer's last paid order date index.
 *
 * @since 6.6.0
 */
class RebuildCustomerLastPurchasedDates extends Tool_Background_Processed_Abstract {

	/**
	 * @var string
	 */
	public $id = 'rebuild_customer_last_purchased_dates';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->title       = __( 'Rebuild customer last order dates', 'automatewoo' );
		$this->description = __( "Rebuild each customer's last paid order date index. Use this after importing historical orders so customer win-back workflows include those orders.", 'automatewoo' );
	}

	/**
	 * @param array $args
	 * @return bool|\WP_Error
	 */
	public function process( $args ) {
		$customer_ids = ( new Customer_Query() )
			->set_ordering( 'id', 'ASC' )
			->get_results_as_ids();

		$tasks = [];

		foreach ( $customer_ids as $customer_id ) {
			$tasks[] = [
				'tool_id'     => $this->get_id(),
				'customer_id' => $customer_id,
			];
		}

		return $this->start_background_job( $tasks );
	}

	/**
	 * Do validation in the validate_process() method not here.
	 *
	 * @param array $args
	 */
	public function display_confirmation_screen( $args ) {
		$count = ( new Customer_Query() )->get_count();

		$text          = __( 'Are you sure you want to rebuild the last paid order date index for all customers?', 'automatewoo' );
		$number_string = sprintf(
			/* translators: %d Number of customers that will be updated. */
			_n( '%d customer will be updated.', '%d customers will be updated.', $count, 'automatewoo' ),
			$count
		);

		echo '<p>' . esc_html( $text . ' ' . $number_string ) . '</p>';
	}

	/**
	 * @param array $task
	 */
	public function handle_background_task( $task ) {
		$customer_id = isset( $task['customer_id'] ) ? Clean::id( $task['customer_id'] ) : false;

		if ( ! $customer_id ) {
			return;
		}

		$customer = Customer_Factory::get( $customer_id );

		if ( ! $customer ) {
			return;
		}

		$customer->recache_date_last_purchased();
	}
}
