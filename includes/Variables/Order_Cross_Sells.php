<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Cross_Sells
 */
class Variable_Order_Cross_Sells extends Variable_Abstract_Product_Display {

	public $support_limit_field = true;


	function load_admin_details() {
		parent::load_admin_details();

		$this->description = sprintf(
			__( "Displays a product listing of cross sells based on the items in an order. Be sure to <a href='%s' target='_blank'>set up cross sells</a> before using.", 'automatewoo'),
			'http://docs.woothemes.com/document/related-products-up-sells-and-cross-sells/'
		);
	}


	/**
	 * @param $order \WC_Order
	 * @param $parameters array
	 * @param $workflow
	 * @return string
	 */
	function get_value( $order, $parameters, $workflow ) {

		$limit = isset( $parameters['limit'] ) ? absint( $parameters['limit'] ) : 8;
		$template = isset( $parameters['template'] ) ? $parameters['template'] : false;

		$cross_sells = aw_get_order_cross_sells( $order );

		if ( empty( $cross_sells ) ) {
			return false;
		}

		$query_args = wp_parse_args(
			[
				'include' => $cross_sells,
				'limit'   => $limit,
			],
			$this->get_default_product_query_args()
		);

		$products = aw_get_products( $query_args );

		$args = array_merge( $this->get_default_product_template_args( $workflow, $parameters ), [
			'products' => $products
		]);

		return $this->get_product_display_html( $template, $args );
	}
}
