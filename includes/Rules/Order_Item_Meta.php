<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Item_Meta
 */
class Order_Item_Meta extends Abstract_Meta {

	/** @var string */
	public $data_item = 'order_item';

	/**
	 * Init the rule
	 */
	public function init() {
		$this->title = __( 'Order Line Item - Custom Field', 'automatewoo' );
	}


	/**
	 * Validate the rule based on options set by a workflow
	 *
	 * @param \WC_Order_Item_Product $order_item
	 * @param string                 $compare_type
	 * @param array                  $value_data
	 *
	 * @return bool
	 */
	public function validate( $order_item, $compare_type, $value_data ) {

		$value_data = $this->prepare_value_data( $value_data );

		if ( ! is_array( $value_data ) ) {
			return false;
		}

		return $this->validate_meta( wc_get_order_item_meta( $order_item->get_id(), $value_data['key'] ), $compare_type, $value_data['value'] );
	}
}
