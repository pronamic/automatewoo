<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use AutomateWoo\Clean;
use WC_Abstract_Order;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Order data type class.
 */
class Order extends AbstractDataType {

	/**
	 * @param $item
	 * @return bool
	 */
	function validate( $item ) {
		return $item instanceof WC_Abstract_Order;
	}


	/**
	 * @param \WC_Order $item
	 * @return mixed
	 */
	function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return mixed
	 */
	function decompress( $compressed_item, $compressed_data_layer ) {
		$id = Clean::id( $compressed_item );

		if ( ! $id && isset( $compressed_data_layer['booking'] ) ) {
			return new WC_Order();
		}

		if ( ! $id ) {
			return false;
		}

		$order = wc_get_order( $id );

		if ( ! $order || $order->get_status() === 'trash' ) {
			return false;
		}

		return $order;
	}

	/**
	 * Get singular name for data type.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_singular_name() {
		return __( 'Order', 'automatewoo' );
	}

	/**
	 * Get plural name for data type.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_plural_name() {
		return __( 'Orders', 'automatewoo' );
	}


}
