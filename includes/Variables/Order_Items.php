<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_Items
 */
class Variable_Order_Items extends Variable_Abstract_Product_Display {

	/**
	 * Support order table.
	 *
	 * @var boolean
	 */
	public $supports_order_table = true;

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Displays the products in an order. Please note this variable returns HTML.', 'automatewoo' );
	}

	/**
	 * @param \WC_Order $order
	 * @param array     $parameters
	 * @param Workflow  $workflow
	 * @return string
	 */
	public function get_value( $order, $parameters, $workflow ) {

		$template = isset( $parameters['template'] ) ? $parameters['template'] : false;
		$items    = $order->get_items();
		$products = [];

		foreach ( $items as $item ) {
			$products[] = $item->get_product();
		}

		$args = array_merge(
			$this->get_default_product_template_args( $workflow, $parameters ),
			[
				'products' => array_filter( $products ),
				'order'    => $order,
			]
		);

		return $this->get_product_display_html( $template, $args );
	}
}
