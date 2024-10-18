<?php

namespace AutomateWoo\Async_Events;

use AutomateWoo\Clean;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Pending
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class Order_Pending extends Abstract_Async_Event {

	/**
	 * Order pending uses the order created async event.
	 *
	 * @var array
	 */
	protected $event_dependencies = [ 'order_created' ];

	/**
	 * Init order pending async event.
	 */
	public function init() {
		add_action( 'automatewoo/async/order_created', [ $this, 'schedule_pending_check' ] );
		add_action( $this->get_hook_name(), [ $this, 'do_pending_check' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'maybe_clear_scheduled_check' ], 10, 2 );
	}

	/**
	 * Get the async event hook name.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function get_hook_name(): string {
		return 'automatewoo_check_for_pending_order';
	}

	/**
	 * Schedule order pending event in 5 mins.
	 *
	 * @param int $order_id
	 */
	public function schedule_pending_check( $order_id ) {
		$delay = apply_filters( 'automatewoo_order_pending_check_delay', 5 ) * 60;
		$this->action_scheduler->schedule_single( gmdate( 'U' ) + $delay, $this->get_hook_name(), [ (int) $order_id ] );
	}

	/**
	 * Clear scheduled event if order is no longer pending.
	 *
	 * @param int    $order_id
	 * @param string $old_status
	 */
	public function maybe_clear_scheduled_check( $order_id, $old_status ) {
		if ( $old_status === 'pending' ) {
			$this->action_scheduler->cancel( 'automatewoo_check_for_pending_order', [ (int) $order_id ] );
		}
	}

	/**
	 * Maybe do order pending action.
	 *
	 * @param int $order_id
	 */
	public function do_pending_check( $order_id ) {
		$order = wc_get_order( Clean::id( $order_id ) );

		if ( $order && $order->has_status( 'pending' ) ) {
			do_action( 'automatewoo_order_pending', $order_id );
		}
	}
}
