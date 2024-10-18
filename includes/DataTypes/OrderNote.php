<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use AutomateWoo\Order_Note as OrderNoteModel;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * OrderNote data type class.
 */
class OrderNote extends AbstractDataType {

	/**
	 * @param $item
	 * @return bool
	 */
	function validate( $item ) {
		return $item instanceof OrderNoteModel;
	}


	/**
	 * @param $item
	 * @return mixed
	 */
	function compress( $item ) {
		return $item->id;
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

		if ( $comment = get_comment( $compressed_item ) ) {
			return new OrderNoteModel( $comment->comment_ID, $comment->comment_content, $comment->comment_post_ID );
		}
	}

}
