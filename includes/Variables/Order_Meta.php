<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Variable_Order_Meta
 */
class Variable_Order_Meta extends Variable_Abstract_Meta {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the value of an order custom field.', 'automatewoo' );

		$field = new Fields\Text();
		$field->set_name( 'key' );
		$field->set_description( __( 'The meta_key of the field you would like to display.', 'automatewoo' ) );
		$field->set_required();
		$field->add_data_attr( 'aw-internal-meta-keys', implode( ' ', Order_Helper::get_internal_meta_keys() ) );
		$field->meta = [
			'internal_meta_key_warning' => Order_Helper::get_internal_meta_key_warning(),
		];

		$this->add_parameter_field( $field );
	}

	/**
	 * @param \WC_Order $order
	 * @param array     $parameters
	 * @return string
	 */
	public function get_value( $order, $parameters ) {
		if ( $parameters['key'] ) {
			return (string) Order_Helper::get_order_meta_value( $order, $parameters['key'] );
		}
		return '';
	}
}
