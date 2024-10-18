<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Customer_Opted_In
 */
class Trigger_Customer_Opted_In extends Trigger {

	/**
	 * Sets the supplied data items.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ 'customer' ];

	/**
	 * Load admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Customer Opted In', 'automatewoo' );
		$this->description = __( 'Fires when a customer chooses to opt-in to all workflows.', 'automatewoo' );
		$this->group       = __( 'Customers', 'automatewoo' );
	}

	/**
	 * Register trigger hook.
	 */
	public function register_hooks() {
		add_action( 'automatewoo/customer/opted_in', [ $this, 'handle_opt_in' ] );
	}

	/**
	 * Handle opt-in.
	 *
	 * @param Customer $customer
	 */
	public function handle_opt_in( $customer ) {
		$this->maybe_run(
			[
				'customer' => $customer,
			]
		);
	}
}
