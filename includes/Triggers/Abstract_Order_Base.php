<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Trigger_Abstract_Order_Base
 */
abstract class Trigger_Abstract_Order_Base extends Trigger {

	/** @var bool - define if the trigger runs per order or per line item, used by the manual order trigger */
	public $is_run_for_each_line_item = false;


	function __construct() {

		if ( $this->is_run_for_each_line_item ) {
			$this->supplied_data_items = [ 'customer', 'order', 'product', 'order_item' ];
		}
		else {
			$this->supplied_data_items = [ 'customer', 'order' ];
		}

		parent::__construct();
	}


	function load_admin_details() {
		$this->group = __( 'Orders', 'automatewoo' );
	}


	/**
	 * @param int|\WC_Order $order
	 * @return \WC_Order|false
	 */
	function get_order( $order ) {

		if ( is_object( $order ) && is_a( $order, 'WC_Abstract_Order' ) ) {
			return $order;
		}
		elseif ( is_numeric( $order ) ) {
			return wc_get_order( $order );
		}
		return false;
	}


	/**
	 * @param \WC_Order|int $order
	 */
	function trigger_for_order( $order ) {
		if ( ! $order = $this->get_order( $order ) ) {
			return;
		}

		$this->maybe_run([
			'order' => $order,
			'customer' => Customer_Factory::get_by_order( $order ),
		]);
	}


	/**
	 * @param int|\WC_Order $order
	 */
	function trigger_for_each_order_item( $order ) {
		if ( ! $order = $this->get_order( $order ) ) {
			return;
		}

		$customer = Customer_Factory::get_by_order( $order );

		foreach ( $order->get_items() as $order_item_id => $order_item ) {
			$this->maybe_run( [
				'order'      => $order,
				'order_item' => $order_item,
				'customer'   => $customer,
				'product'    => $order_item->get_product(),
			] );
		}
	}


}
