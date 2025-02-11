<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_Cross_Sells
 */
class Variable_Order_Cross_Sells extends Variable_Abstract_Product_Display {

	/**
	 * Support the limit field.
	 *
	 * @var bool
	 */
	public $support_limit_field = true;

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();

		$this->description = sprintf(
			/* translators: %1$s documentation link start, %2$s documentation link end. */
			__( 'Displays a product listing of cross sells based on the items in an order. Be sure to %1$sset up cross sells%2$s before using.', 'automatewoo' ),
			'<a href="http://docs.woothemes.com/document/related-products-up-sells-and-cross-sells/" target="_blank">',
			'</a>'
		);
	}

	/**
	 * @param \WC_Order $order
	 * @param array     $parameters
	 * @param Workflow  $workflow
	 * @return string
	 */
	public function get_value( $order, $parameters, $workflow ) {
		$limit    = isset( $parameters['limit'] ) ? absint( $parameters['limit'] ) : 8;
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

		$args = array_merge(
			$this->get_default_product_template_args( $workflow, $parameters ),
			[ 'products' => $products ]
		);

		return $this->get_product_display_html( $template, $args );
	}
}
