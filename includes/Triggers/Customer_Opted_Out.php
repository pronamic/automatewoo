<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Customer_Opted_Out
 */
class Trigger_Customer_Opted_Out extends Trigger {

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
		$this->title       = __( 'Customer Opted Out', 'automatewoo' );
		$this->description = __( 'Fires when a customer chooses to opt-out from all workflows.', 'automatewoo' );
		$this->group       = __( 'Customers', 'automatewoo' );
	}

	/**
	 * Register trigger hook.
	 */
	public function register_hooks() {
		add_action( 'automatewoo/customer/opted_out', [ $this, 'handle_opt_out' ] );
	}

	/**
	 * Handle opt-out.
	 *
	 * @param Customer $customer
	 */
	public function handle_opt_out( $customer ) {
		$this->maybe_run(
			[
				'customer' => $customer,
			]
		);
	}
}
