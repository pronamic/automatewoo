<?php

namespace AutomateWoo\Traits;

use ArrayAccess;
use AutomateWoo\Exceptions\InvalidArgument;

/**
 * ArrayValidator Trait
 *
 * @since 5.1.0
 */
trait ArrayValidator {

	use StringValidator;

	/**
	 * Validate that an item is an array or an object that can be accessed as an array.
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @throws InvalidArgument When $value is not an array or an ArrayAccess object.
	 */
	private function validate_is_array( $value ) {
		if ( ! is_array( $value ) && ! $value instanceof ArrayAccess ) {
			throw InvalidArgument::invalid_parameter_type( 'array' );
		}
	}

	/**
	 * Validate that an item is an array of arrays, or objects that are accessible as an array.
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @throws InvalidArgument When $value is not an array of arrays.
	 */
	private function validate_array_of_arrays( $value ) {
		$this->validate_is_array( $value );
		foreach ( $value as $item ) {
			$this->validate_is_array( $item );
		}
	}

	/**
	 * Validate that an item is an array of strings.
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @throws InvalidArgument When $value is not an array of strings.
	 */
	private function validate_array_of_strings( $value ) {
		$this->validate_is_array( $value );
		foreach ( $value as $item ) {
			$this->validate_is_string( $item );
		}
	}

	/**
	 * Validate that an item is an array or an object that can be accessed as an array and the array is not empty.
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @throws InvalidArgument When $value is not an array or it is empty.
	 */
	private function validate_is_non_empty_array( $value ) {
		$this->validate_is_array( $value );
		if ( empty( $value ) ) {
			throw InvalidArgument::empty();
		}
	}
}
