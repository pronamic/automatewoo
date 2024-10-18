<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_File_Not_Yet_Downloaded.
 *
 * @since 5.6.6
 * @package AutomateWoo
 */
class Trigger_File_Not_Yet_Downloaded extends Trigger_Abstract_Downloadable_Content {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'File Not Yet Downloaded', 'automatewoo' );
		$this->description = __( 'This trigger will fire if a file has not yet been downloaded after a set period of time.', 'automatewoo' );
		parent::load_admin_details();
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'woocommerce_order_status_processing', array( $this, 'queue_download_reminders' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'queue_download_reminders' ) );
		add_action( 'woocommerce_download_product', array( $this, 'maybe_clear_queued_events' ), 10, 6 );
	}

	/**
	 * Queue 'not_yet_downloaded' events for each downloadable file in the order.
	 *
	 * @param int $order_id
	 */
	public function queue_download_reminders( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Check if order status is processing and downloadable files are not permitted.
		if ( $order->has_status( 'processing' ) && ! $order->is_download_permitted() ) {
			return;
		}

		$downloadable_files = $order->get_downloadable_items();
		if ( empty( $downloadable_files ) ) {
			return;
		}

		$customer = Customer_Factory::get_by_order( $order );

		foreach ( $downloadable_files as $item ) {
			$product_id  = Clean::id( $item['product_id'] );
			$download_id = Clean::string( $item['download_id'] );
			$product     = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			// Maybe run workflows for given downloadable file.
			$this->maybe_run_workflows( $download_id, $product, $order, $customer, true );
		}
	}

	/**
	 * Maybe clear queued events.
	 *
	 * @param string $user_email  User Email address.
	 * @param string $order_key   Order key.
	 * @param int    $product_id  Product ID.
	 * @param int    $user_id     User ID.
	 * @param int    $download_id Download ID.
	 * @param int    $order_id    Order ID.
	 */
	public function maybe_clear_queued_events( $user_email, $order_key, $product_id, $user_id, $download_id, $order_id ) {
		$query = new Queue_Query();
		$query->where_workflow( $this->get_workflow_ids() );
		$query->where_order( $order_id );
		$query->where_product( $product_id );
		$query->where_download( $download_id );

		foreach ( $query->get_results() as $event ) {
			$event->delete();
		}
	}

	/**
	 * Ensures file is not downloaded by user while sitting in queue
	 * We are already clearing queued events on file download, but this is an extra check
	 * To ensure the file is not downloaded before the event is run
	 *
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		$download = $workflow->data_layer()->get_item( 'download' );

		if ( ! $download || $download->get_download_count() > 0 ) {
			return false;
		}

		return true;
	}
}
