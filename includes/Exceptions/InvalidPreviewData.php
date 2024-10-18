<?php

namespace AutomateWoo\Exceptions;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * InvalidPreviewData exception class.
 *
 * @since   4.9.2
 * @package AutomateWoo\Exceptions
 */
class InvalidPreviewData extends InvalidArgumentException implements UserFacingException {

	/**
	 * Creates a new instance of the exception when an action can't be previewed.
	 *
	 * @return static
	 */
	public static function invalid_action() {
		return new static( __( 'This action can not be previewed.', 'automatewoo' ) );
	}

	/**
	 * Creates a new instance of the exception with a generic message.
	 *
	 * @return static
	 */
	public static function generic() {
		return new static( __( 'There was an error generating the preview.', 'automatewoo' ) );
	}

	/**
	 * Creates a new instance of the exception when a valid order isn't found.
	 *
	 * @return static
	 */
	public static function order_not_found() {
		return self::data_item_needed( 'order' );
	}

	/**
	 * Get an exception for when a preview data item was not found.
	 *
	 * @param string $data_type The type of data that is needed.
	 *
	 * @return static
	 */
	public static function data_item_needed( string $data_type ) {
		/* translators: Data type. */
		return new static( sprintf( __( 'A valid "%s" must exist to generate the preview.', 'automatewoo' ), $data_type ) );
	}
}
