<?php

namespace AutomateWoo\Traits;

use AutomateWoo\Exceptions\InvalidArgument;

/**
 * Class StringValidator
 *
 * @since 5.1.0
 */
trait StringValidator {

	/**
	 * Validate that an item is a string.
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @throws InvalidArgument When $value is not a string.
	 */
	public function validate_is_string( $value ) {
		if ( ! is_string( $value ) ) {
			throw InvalidArgument::invalid_parameter_type( 'string' );
		}
	}
}
