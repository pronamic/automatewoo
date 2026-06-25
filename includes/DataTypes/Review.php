<?php

namespace AutomateWoo\DataTypes;

use AutomateWoo\Review as ReviewModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Review data type class.
 */
class Review extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof ReviewModel;
	}


	/**
	 * @param ReviewModel $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * @param int|string|null $compressed_item
	 * @param array           $compressed_data_layer
	 * @return ReviewModel|false
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}
		return new ReviewModel( $compressed_item );
	}
}
