<?php

namespace AutomateWoo\RuleQuickFilters\Clauses;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class ArrayClause
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters\Clauses
 */
class ArrayClause extends AbstractClause {

	/**
	 * ArrayClause constructor.
	 *
	 * @param string $property The property to filter against.
	 * @param string $operator Should be a valid database WHERE operator.
	 * @param array  $value    The quick filter clause value.
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	public function __construct( $property, $operator, $value ) {
		parent::__construct( $property, $operator, $value );
	}

	/**
	 * Validates the clause value.
	 *
	 * @param mixed $value
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	protected function validate_value( $value ) {
		if ( ! is_array( $value ) ) {
			throw new InvalidArgumentException();
		}
	}
}
