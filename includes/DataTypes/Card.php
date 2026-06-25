<?php

namespace AutomateWoo\DataTypes;

use WC_Payment_Token;
use WC_Payment_Token_CC;
use WC_Payment_Tokens;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Card data type class
 *
 * @since 3.7
 */
class Card extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof WC_Payment_Token_CC;
	}


	/**
	 * @param WC_Payment_Token_CC $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * @param int|string|null $compressed_item
	 * @param array           $compressed_data_layer
	 * @return WC_Payment_Token_CC|WC_Payment_Token|false
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}
		return WC_Payment_Tokens::get( absint( $compressed_item ) );
	}
}
