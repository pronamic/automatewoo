<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Trigger_Customer_New_Account
 */
class Trigger_Customer_New_Account extends Trigger {

	public $supplied_data_items = [ 'customer' ];

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var array|string
	 */
	protected $required_async_events = 'user_registered';


	function load_admin_details() {
		$this->title = __( 'Customer Account Created', 'automatewoo' );
		$this->group = __( 'Customers', 'automatewoo' );
	}


	function register_hooks() {
		if ( AUTOMATEWOO_DISABLE_ASYNC_CUSTOMER_NEW_ACCOUNT ) {
			add_action( 'automatewoo/user_registered', [ $this, 'user_registered' ] );
		}
		else {
			add_action( 'automatewoo/async/user_registered', [ $this, 'user_registered' ] );
		}
	}


	/**
	 * @param $user_id
	 */
	function user_registered( $user_id ) {
		$this->maybe_run([
			'customer' => Customer_Factory::get_by_user_id( $user_id )
		]);
	}

}
