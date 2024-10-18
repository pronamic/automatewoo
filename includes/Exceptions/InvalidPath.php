<?php

namespace AutomateWoo\Exceptions;

use InvalidArgumentException;

/**
 * InvalidPath class.
 *
 * @package AutomateWoo\Exceptions
 * @since   5.1.0
 */
class InvalidPath extends InvalidArgumentException implements Exception {

	/**
	 * Return a new instance of the exception when a file does not exist.
	 *
	 * @param string $path The provided path.
	 *
	 * @return static
	 */
	public static function file_does_not_exist( $path ) {
		return new static( sprintf( 'Invalid argument: file "%s" does not exist.', $path ) );
	}

	/**
	 * Return a new instance of the exception when a path is not a directory.
	 *
	 * @param string $path The provided path.
	 *
	 * @return static
	 */
	public static function path_not_directory( $path ) {
		return new static( sprintf( 'Invalid argument: path "%s" is not a directory.', $path ) );
	}
}
