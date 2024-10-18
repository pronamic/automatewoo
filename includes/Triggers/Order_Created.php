<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Trigger_Order_Created
 */
class Trigger_Order_Created extends Trigger_Abstract_Order_Base {

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var string|array
	 */
	protected $required_async_events = 'order_created';


	function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Order Created', 'automatewoo' );
		$this->description = __( 'This trigger fires after an order is created in the database. At checkout this happens before payment is confirmed.', 'automatewoo' );
	}


	function register_hooks() {
		add_action( 'automatewoo/async/order_created', [ $this, 'trigger_for_order' ] );
	}

}
