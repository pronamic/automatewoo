<?php

namespace AutomateWoo\Exceptions;

use LogicException;

/**
 * InvalidIntegration exception class.
 *
 * Thrown when a required integration is invalid.
 *
 * @version 5.3.0
 */
class InvalidIntegration extends LogicException implements Exception {

	/**
	 * Create exception for when an integration plugin is not active.
	 *
	 * @param string $name
	 *
	 * @return static
	 */
	public static function plugin_not_active( string $name ): InvalidIntegration {
		return new static( sprintf( '%s plugin is not active.', $name ) );
	}

	/**
	 * Create exception for when an integration plugin version is not supported.
	 *
	 * @param string $name
	 * @param string $min_required_version
	 *
	 * @return static
	 */
	public static function plugin_version_not_supported( string $name, string $min_required_version ): InvalidIntegration {
		return new static( sprintf( 'The version of %s is not supported. The minimum required version is %s.', $name, $min_required_version ) );
	}
}
