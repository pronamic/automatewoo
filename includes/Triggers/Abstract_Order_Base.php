<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Trigger_Abstract_Order_Base
 */
abstract class Trigger_Abstract_Order_Base extends Trigger {

	const OPTION_ONLY_RUN_FOR_CHECKOUT_ORDERS = 'only_run_for_checkout_orders';

	/** @var bool - define if the trigger runs per order or per line item, used by the manual order trigger */
	public $is_run_for_each_line_item = false;


	/**
	 * Trigger_Abstract_Order_Base constructor.
	 */
	public function __construct() {

		if ( $this->is_run_for_each_line_item ) {
			$this->supplied_data_items = [ 'customer', 'order', 'product', 'order_item' ];
		} else {
			$this->supplied_data_items = [ 'customer', 'order' ];
		}

		parent::__construct();
	}


	/**
	 * Load admin details for the trigger.
	 */
	public function load_admin_details() {
		$this->group = __( 'Orders', 'automatewoo' );
	}


	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		return $this->validate_checkout_order_source( $workflow );
	}


	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		return $this->validate_checkout_order_source( $workflow );
	}


	/**
	 * @param int|\WC_Order $order
	 * @return \WC_Order|false
	 */
	public function get_order( $order ) {

		if ( is_object( $order ) && is_a( $order, 'WC_Abstract_Order' ) ) {
			return $order;
		} elseif ( is_numeric( $order ) ) {
			return wc_get_order( $order );
		}
		return false;
	}


	/**
	 * Adds an option to ignore orders created outside frontend checkout.
	 */
	protected function add_field_only_run_for_checkout_orders() {
		$field = ( new Fields\Checkbox() )
			->set_name( self::OPTION_ONLY_RUN_FOR_CHECKOUT_ORDERS )
			->set_title( __( 'Only run for checkout orders', 'automatewoo' ) )
			->set_description( __( 'Skips orders created by admin, REST API, import tools, and other non-checkout sources.', 'automatewoo' ) );

		$this->add_field( $field );
	}


	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	protected function validate_checkout_order_source( $workflow ) {
		// Only honor the option on triggers that actually offer the field, so stored
		// option values can never block orders on triggers that hide the checkbox
		// (e.g. subscription order triggers, where renewals are never checkout-created).
		if ( ! $this->get_field( self::OPTION_ONLY_RUN_FOR_CHECKOUT_ORDERS ) ) {
			return true;
		}

		if ( ! $workflow->get_trigger_option( self::OPTION_ONLY_RUN_FOR_CHECKOUT_ORDERS ) ) {
			return true;
		}

		$order = $workflow->data_layer()->get_order();

		return $order && $this->is_order_created_via_checkout( $order );
	}


	/**
	 * @param \WC_Order $order
	 * @return bool
	 */
	protected function is_order_created_via_checkout( $order ) {
		return in_array( $order->get_created_via(), [ 'checkout', 'store-api' ], true ) || (bool) $order->get_cart_hash();
	}


	/**
	 * @param \WC_Order|int $order
	 */
	public function trigger_for_order( $order ) {
		$order = $this->get_order( $order );
		if ( ! $order ) {
			return;
		}

		$this->maybe_run(
			[
				'order'    => $order,
				'customer' => Customer_Factory::get_by_order( $order ),
			]
		);
	}


	/**
	 * @param int|\WC_Order $order
	 */
	public function trigger_for_each_order_item( $order ) {
		$order = $this->get_order( $order );
		if ( ! $order ) {
			return;
		}

		$customer = Customer_Factory::get_by_order( $order );

		foreach ( $order->get_items() as $order_item_id => $order_item ) {
			$this->maybe_run(
				[
					'order'      => $order,
					'order_item' => $order_item,
					'customer'   => $customer,
					'product'    => $order_item->get_product(),
				]
			);
		}
	}
}
