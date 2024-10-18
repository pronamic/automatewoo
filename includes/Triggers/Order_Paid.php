<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Trigger_Order_Paid
 */
class Trigger_Order_Paid extends Trigger_Abstract_Order_Base {

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var string|array
	 */
	protected $required_async_events = 'order_paid';


	function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Order Paid', 'automatewoo' );
		$this->description = __( 'Triggers at the end of the payment process after the order status has been changed and stock has been reduced.', 'automatewoo' );
	}


	function register_hooks() {
		add_action( 'automatewoo/order/paid_async', [ $this, 'trigger_for_order' ] );
	}

}
