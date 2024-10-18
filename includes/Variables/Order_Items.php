<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Items
 */
class Variable_Order_Items extends Variable_Abstract_Product_Display {


	public $supports_order_table = true;


	function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( "Displays the products in an order. Please note this variable returns HTML.", 'automatewoo');
	}


	/**
	 * @param $order \WC_Order
	 * @param $parameters array
	 * @param $workflow
	 * @return string
	 */
	function get_value( $order, $parameters, $workflow ) {

		$template = isset( $parameters['template'] ) ? $parameters['template'] : false;
		$items = $order->get_items();
		$products = [];

		foreach ( $items as $item ) {
			$products[] = $item->get_product();
		}

		$args = array_merge( $this->get_default_product_template_args( $workflow, $parameters ), [
			'products' => array_filter( $products ),
			'order' => $order
		]);

		return $this->get_product_display_html( $template, $args );
	}

}
