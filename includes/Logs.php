<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Logs management class.
 *
 * @since 3.8
 */
class Logs {

	/**
	 * Returns the meta key that a data item is mapped to in log meta.
	 *
	 * @param $data_type_id string
	 * @return bool|string
	 */
	static function get_data_layer_storage_key( $data_type_id ) {
		$storage_keys = apply_filters( 'automatewoo/log/data_layer_storage_keys', [
			'cart' => 'cart_id',
			'category' => 'category_id',
			'comment' => 'comment_id',
			'guest' => 'guest_email',
			'order' => 'order_id',
			'order_item' => 'order_item_id',
			'order_note' => 'order_note_id',
			'product' => 'product_id',
			'subscription' => 'subscription_id',
			'tag' => 'tag_id',
			'user' => 'user_id',
			'wishlist' => 'wishlist_id',
			'workflow' => 'workflow_id',
		]);

		if ( isset( $storage_keys[ $data_type_id ] ) ) {
			return $storage_keys[ $data_type_id ];
		}
		else {
			return '_data_layer_' . $data_type_id;
		}
	}


	/**
	 * @param $data_type_id
	 * @param $data_item : must be validated
	 * @return mixed
	 */
	static function get_data_layer_storage_value( $data_type_id, $data_item ) {
		$value = false;

		if ( $data_type = DataTypes::get( $data_type_id ) ) {
			$value = $data_type->compress( $data_item );
		}

		return $value;
	}

}
