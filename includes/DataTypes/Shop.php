<?php

namespace AutomateWoo\DataTypes;

use AutomateWoo\ShopDataItem;

defined( 'ABSPATH' ) || exit;

/**
 * Shop data type class.
 *
 * 'Shop' is a psuedo data type since the shop is always available in every workflow and doesn't
 * need to be stored in the workflow queue or logs.
 *
 * @since 5.1.0
 */
class Shop extends AbstractDataType {

	/**
	 * The shop data item is always valid.
	 *
	 * @param mixed $item
	 *
	 * @return bool
	 */
	public function validate( $item ) {
		return true;
	}

	/**
	 * The shop data item doesn't need compressing.
	 *
	 * @param mixed $item
	 *
	 * @return null
	 */
	public function compress( $item ) {
		return null;
	}

	/**
	 * Decompress a data item.
	 * Usually involves getting the full object from an ID.
	 *
	 * @param mixed $compressed_item
	 * @param array $compressed_data_layer
	 *
	 * @return ShopDataItem
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		return new ShopDataItem();
	}
}
