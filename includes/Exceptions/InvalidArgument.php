<?php

namespace AutomateWoo\Exceptions;

use InvalidArgumentException;

/**
 * Class InvalidArgument
 *
 * @since 5.1.0
 */
class InvalidArgument extends InvalidArgumentException implements Exception {

	/**
	 * Return a new instance of an exception for an invalid type.
	 *
	 * @param string $type The type that was expected, e.g. string, bool, etc.
	 *
	 * @return static
	 */
	public static function invalid_parameter_type( string $type ): InvalidArgument {
		return new static( sprintf( 'Invalid parameter type. The type should be "%s".', $type ) );
	}

	/**
	 * Return a new instance of an exception for an invalid argument.
	 *
	 * @param string $valid_argument_description
	 *
	 * @return static
	 */
	public static function invalid_argument( string $valid_argument_description ): InvalidArgument {
		return new static( sprintf( 'Invalid argument. The argument should be "%s".', $valid_argument_description ) );
	}

	/**
	 * Return a new instance of an exception for an missing required argument.
	 *
	 * @param string $name The name of the required argument.
	 *
	 * @return static
	 */
	public static function missing_required( string $name ): InvalidArgument {
		return new static( sprintf( 'A "%s" argument is required.', $name ) );
	}

	/**
	 * Return a new instance of an exception for an invalid argument.
	 *
	 * @return static
	 */
	public static function empty(): InvalidArgument {
		return new static( 'Invalid argument. The argument should not be empty.' );
	}
}
