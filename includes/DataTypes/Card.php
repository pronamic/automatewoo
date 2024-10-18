<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use WC_Payment_Token;
use WC_Payment_Token_CC;
use WC_Payment_Tokens;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Card data type class
 * @since 3.7
 */
class Card extends AbstractDataType {

	/**
	 * @param $item
	 * @return bool
	 */
	function validate( $item ) {
		return $item instanceof WC_Payment_Token_CC;
	}


	/**
	 * @param WC_Payment_Token_CC $item
	 * @return mixed
	 */
	function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return WC_Payment_Token_CC|WC_Payment_Token|false
	 */
	function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}
		return WC_Payment_Tokens::get( absint( $compressed_item ) );
	}

}
