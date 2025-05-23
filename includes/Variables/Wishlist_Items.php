<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Wishlist_Items
 */
class Variable_Wishlist_Items extends Variable_Abstract_Product_Display {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( 'Display a product listing of the items in the wishlist.', 'automatewoo' );
	}

	/**
	 * @param Wishlist $wishlist
	 * @param array    $parameters
	 * @param Workflow $workflow
	 * @return string
	 */
	public function get_value( $wishlist, $parameters, $workflow ) {

		$products = [];
		$template = isset( $parameters['template'] ) ? $parameters['template'] : false;

		foreach ( $wishlist->get_items() as $product_id ) {
			$products[] = wc_get_product( $product_id );
		}

		$args = array_merge(
			$this->get_default_product_template_args( $workflow, $parameters ),
			[ 'products' => $products ]
		);

		return $this->get_product_display_html( $template, $args );
	}
}
