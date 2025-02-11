<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Product_Meta
 */
class Variable_Product_Meta extends Variable_Abstract_Meta {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( "Displays a product's custom field.", 'automatewoo' );
	}

	/**
	 * @param \WC_Product $product
	 * @param array       $parameters
	 * @return string
	 */
	public function get_value( $product, $parameters ) {
		if ( ! $parameters['key'] ) {
			return '';
		}

		$value = $product->get_meta( $parameters['key'] );

		// Look for parent meta
		if ( empty( $value ) && $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			$value  = $parent ? $parent->get_meta( $parameters['key'] ) : '';
		}

		return (string) $value;
	}
}
