<?php

namespace AutomateWoo;

use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Order_Trigger_Action
 * @since 2.3
 */
class Action_Order_Trigger_Action extends Action {

	/**
	 * The data items required by the action.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'order' ];

	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Trigger Order Action', 'automatewoo' );
		$this->group       = __( 'Order', 'automatewoo' );
		$this->description = __( 'Not to be confused with AutomateWoo actions this action can trigger a WooCommerce order action. They can normally be found in the in the top right of of the order edit view.', 'automatewoo' );
	}

	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {

		$action = new Fields\Select();
		$action->set_name( 'order_action' );
		$action->set_title( __( 'Order action', 'automatewoo' ) );
		$action->set_required();
		$action->set_options( $this->get_order_actions() );

		$this->add_field( $action );
	}

	/**
	 * Gets a list of WooCommerce order actions via the 'woocommerce_order_actions' filter.
	 *
	 * Because some 3rd party code (e.g. WC Subscriptions, WC Payments) expect a global $theorder object when this
	 * filter runs we set dummy order object avoid errors.
	 *
	 * @see \WC_Meta_Box_Order_Actions::output
	 *
	 * @since 5.5.1
	 *
	 * @return array
	 */
	protected function get_order_actions() {
		global $theorder;

		if ( ! is_object( $theorder ) ) {
			$theorder = new WC_Order();
		}

		// @since 6.0.18 The second param null was added.
		$actions = (array) apply_filters(
			'woocommerce_order_actions',
			[
				'regenerate_download_permissions' => __( 'Generate download permissions', 'automatewoo' ),
			],
			null
		);

		// Clear the dummy order
		$theorder = null;

		return $actions;
	}


	/**
	 * Run the action.
	 *
	 * @throws \Exception When an error occurs.
	 */
	public function run() {
		$order_action_name = $this->get_option( 'order_action' );
		$order             = $this->workflow->data_layer()->get_order();

		if ( ! $order_action_name || ! $order ) {
			return;
		}

		if ( $order_action_name === 'regenerate_download_permissions' ) {
			$data_store = \WC_Data_Store::load( 'customer-download' );
			$data_store->delete_by_order_id( $order->get_id() );
			wc_downloadable_product_permissions( $order->get_id(), true );
		} else {
			do_action( 'woocommerce_order_action_' . sanitize_title( $order_action_name ), $order );
		}
	}
}
