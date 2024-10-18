<?php

namespace AutomateWoo\Workflows\Presets\Storage;

use AutomateWoo\Exceptions\Exception as ExceptionInterface;

/**
 * @class StorageException used in preset storage
 * @since 5.1.0
 */
class StorageException extends \Exception implements ExceptionInterface {

	/**
	 * Create a new exception when a given preset name does not exist.
	 *
	 * @param string $name
	 *
	 * @return static
	 */
	public static function preset_does_not_exist( string $name ): StorageException {
		return new static( sprintf( 'The preset with the name "%s" does not exist.', $name ) );
	}
}
