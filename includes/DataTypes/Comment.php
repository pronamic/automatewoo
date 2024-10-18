<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Comment
 */
class Comment extends AbstractDataType {

	/**
	 * @param $item
	 * @return bool
	 */
	function validate( $item ) {
		return is_object( $item );
	}


	/**
	 * @param $item
	 * @return mixed
	 */
	function compress( $item ) {
		return $item->comment_ID;
	}


	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return \WP_Comment|false
	 */
	function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		return get_comment( $compressed_item );
	}

}
