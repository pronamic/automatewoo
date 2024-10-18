<?php

namespace AutomateWoo\Traits;

use AutomateWoo\Exceptions\InvalidArgument;

/**
 * IntegerValidator trait.
 *
 * @since 5.1.0
 */
trait IntegerValidator {

	/**
	 * Validate that an value is a positive integer.
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @throws InvalidArgument When $value is not valid.
	 */
	public function validate_positive_integer( $value ) {
		if ( ! is_int( $value ) || $value <= 0 ) {
			throw InvalidArgument::invalid_argument( 'a positive integer' );
		}
	}
}
