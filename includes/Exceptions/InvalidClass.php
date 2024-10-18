<?php

namespace AutomateWoo\Exceptions;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * InvalidClass Exception class.
 *
 * @since   4.9.0
 * @package AutomateWoo\Exceptions
 */
class InvalidClass extends InvalidArgumentException implements Exception {

	/**
	 * Create a new instance of the exception when the given class does not exist.
	 *
	 * @param string $class The non-existent class.
	 *
	 * @return static
	 */
	public static function does_not_exist( $class ) {
		return new static( sprintf( 'The class "%s" does not exist.', $class ) );
	}

	/**
	 * Create a new instance of the exception when the given class does not implement the given interface.
	 *
	 * @param string $class     The class that should implement the interface.
	 * @param string $interface An interface name the class should implement.
	 *
	 * @return static
	 */
	public static function does_not_implement_interface( $class, $interface ) {
		return new static( sprintf( 'The class "%s" must implement interface "%s".', $class, $interface ) );
	}
}
