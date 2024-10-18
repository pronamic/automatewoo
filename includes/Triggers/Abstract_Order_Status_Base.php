<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Abstract_Order_Status_Base
 */
abstract class Trigger_Abstract_Order_Status_Base extends Trigger_Abstract_Order_Base {

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var array|string
	 */
	protected $required_async_events = [ 'order_status_changed', 'order_pending' ];

	/**
	 * Target transition status.
	 *
	 * @var string|false
	 */
	public $target_status = false;


	/**
	 * Registers fields used for this trigger.
	 */
	public function load_fields() {
		$this->add_field_validate_queued_order_status();
	}


	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( $this->get_hook_order_status_changed(), [ $this, 'status_changed' ], 10, 3 );
		// add special hook for orders that are created as pending and never paid
		add_action( 'automatewoo_order_pending', [ $this, 'order_pending' ] );
	}


	/**
	 * Trigger a status change when an order is created as pending.
	 *
	 * @param int $order_id
	 */
	public function order_pending( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || ! $order->has_status( 'pending' ) ) {
			return; // ensure order is still pending
		}

		$this->status_changed( $order_id, '', 'pending' );
	}


	/**
	 * Handle a status change event from old status > new status.
	 *
	 * @param int    $order_id
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function status_changed( $order_id, $old_status, $new_status ) {

		if ( ! $new_status ) {
			return; // new status is required
		}

		// target status is used for status specific triggers
		if ( $this->target_status ) {
			if ( $new_status !== $this->target_status ) {
				return;
			}
		}

		// use temp data to store the real status changes since the status of order may have already changed if using async
		Temporary_Data::set( 'order_new_status', $order_id, $new_status );

		if ( $old_status ) {
			Temporary_Data::set( 'order_old_status', $order_id, $old_status );
		}

		if ( $this->is_run_for_each_line_item ) {
			$this->trigger_for_each_order_item( $order_id );
		} else {
			$this->trigger_for_order( $order_id );
		}
	}


	/**
	 * Validate the order status still matches the trigger before a queued event is run.
	 *
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {

		$order = $workflow->data_layer()->get_order();

		if ( ! $order ) {
			return false;
		}

		if ( $this->target_status ) {
			if ( $workflow->get_trigger_option( 'validate_order_status_before_queued_run' ) ) {
				if ( $order->get_status() !== $this->target_status ) {
					return false;
				}
			}
		}

		return true;
	}
}
