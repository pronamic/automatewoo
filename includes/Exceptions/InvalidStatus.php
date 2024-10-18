<?php

namespace AutomateWoo\Exceptions;

use RuntimeException;

/**
 * Class InvalidStatus
 *
 * @since 5.1.0
 */
class InvalidStatus extends RuntimeException implements Exception {

	/**
	 * Create a new exception when the given workflow status is invalid.
	 *
	 * @param string $status The invalid status.
	 *
	 * @return static
	 */
	public static function unknown_status( string $status ): InvalidStatus {
		return new static( sprintf( 'Unknown workflow status: "%s".', $status ) );
	}

	/**
	 * Create a new exception when the given workflow status does not have a mapped post status.
	 *
	 * @param string $status The invalid status.
	 *
	 * @return InvalidStatus
	 */
	public static function no_post_staus( string $status ): InvalidStatus {
		return new static( sprintf( 'The status "%s" requires a corresponding Post status mapping.', $status ) );
	}
}
