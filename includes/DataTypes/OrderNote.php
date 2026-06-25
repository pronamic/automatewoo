<?php

namespace AutomateWoo\DataTypes;

use AutomateWoo\Order_Note as OrderNoteModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OrderNote data type class.
 */
class OrderNote extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof OrderNoteModel;
	}


	/**
	 * @param OrderNoteModel $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->id;
	}


	/**
	 * @param int|string|null $compressed_item
	 * @param array           $compressed_data_layer
	 * @return mixed
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		$comment = get_comment( $compressed_item );
		if ( $comment ) {
			return new OrderNoteModel( $comment->comment_ID, $comment->comment_content, $comment->comment_post_ID );
		}
	}
}
