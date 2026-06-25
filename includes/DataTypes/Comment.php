<?php

namespace AutomateWoo\DataTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Comment
 */
class Comment extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	public function validate( $item ) {
		return is_object( $item );
	}


	/**
	 * @param mixed $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->comment_ID;
	}


	/**
	 * @param int|string|null $compressed_item
	 * @param array           $compressed_data_layer
	 * @return \WP_Comment|false
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		return get_comment( $compressed_item );
	}
}
