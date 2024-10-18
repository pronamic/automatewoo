<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Post data type class.
 */
class Post extends AbstractDataType {

	/**
	 * @param $item
	 * @return bool
	 */
	function validate( $item ) {
		return $item instanceof WP_Post;
	}


	/**
	 * @param WP_Post $item
	 * @return mixed
	 */
	function compress( $item ) {
		return $item->ID;
	}


	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return mixed
	 */
	function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		return get_post( $compressed_item );
	}

}
