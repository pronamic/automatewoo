<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Order_Item_Attribute
 */
class Variable_Order_Item_Attribute extends Variable {


	function load_admin_details() {
		$this->description = __( "Can be used to display the attribute term name when a customer orders a variable product.", 'automatewoo');
		$this->add_parameter_text_field( 'slug', __( "The slug of the product attribute.", 'automatewoo'), true );
	}


	/**
	 * @param array|\WC_Order_Item_Product $order_item
	 * @param $parameters
	 * @return string
	 */
	function get_value( $order_item, $parameters ) {

		if ( empty( $parameters['slug'] ) )
			return false;

		$attribute = 'pa_' . $parameters['slug'];

		$term = $order_item->get_meta( $attribute );

		if ( ! $term )
			return false;

		$term_obj = get_term_by( 'slug', $term, $attribute );

		if ( ! $term_obj || is_wp_error($term_obj) )
			return false;

		return $term_obj->name;
	}
}
