<?php

namespace AutomateWoo\Exceptions;

use UnexpectedValueException;

/**
 * Class InvalidValue.
 *
 * @version 5.3.0
 */
class InvalidValue extends UnexpectedValueException implements UserFacingException {

	/**
	 * Create a new exception for when an item is not found.
	 *
	 * @param string $item_name Optional name of the item.
	 *
	 * @return static
	 */
	public static function item_not_found( string $item_name = '' ): InvalidValue {
		if ( $item_name ) {
			/* translators: Item name. */
			return new static( sprintf( __( 'Item (%s) could not be found.', 'automatewoo' ), $item_name ) );
		} else {
			return new static( __( 'Item could not be found.', 'automatewoo' ) );
		}
	}
}
