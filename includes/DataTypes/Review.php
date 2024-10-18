<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use AutomateWoo\Review as ReviewModel;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Review data type class.
 */
class Review extends AbstractDataType {

	/**
	 * @param $item
	 * @return bool
	 */
	function validate( $item ) {
		return $item instanceof ReviewModel;
	}


	/**
	 * @param ReviewModel $item
	 * @return mixed
	 */
	function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return ReviewModel|false
	 */
	function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}
		return new ReviewModel( $compressed_item );
	}

}
