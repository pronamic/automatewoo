<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use WC_Order_Item;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * OrderItem data type class.
 */
class OrderItem extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	function validate( $item ) {
		return $item instanceof WC_Order_Item;
	}


	/**
	 * @param WC_Order_Item $item
	 *
	 * @return int
	 */
	function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * Order items are retrieved from the order object so we must ensure that an order is always present in the data layer
	 *
	 * @param int   $order_item_id
	 * @param array $compressed_data_layer
	 *
	 * @return mixed
	 */
	function decompress( $order_item_id, $compressed_data_layer ) {
		if ( ! $order_item_id || ! isset( $compressed_data_layer['order'] ) ) {
			return false;
		}

		$order = wc_get_order( $compressed_data_layer['order'] );

		if ( ! $order ) {
			return false;
		}

		return $order->get_item( $order_item_id );
	}

}
