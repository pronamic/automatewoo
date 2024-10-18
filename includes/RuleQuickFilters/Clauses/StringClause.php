<?php

namespace AutomateWoo\RuleQuickFilters\Clauses;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class StringClause
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters\Clauses
 */
class StringClause extends AbstractClause {

	/**
	 * StringClause constructor.
	 *
	 * @param string $property The property to filter against.
	 * @param string $operator Should be a valid database WHERE operator.
	 * @param string $value    The quick filter clause value.
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	public function __construct( $property, $operator, $value ) {
		parent::__construct( $property, $operator, $value );
	}

	/**
	 * Validates the clause value.
	 *
	 * @param string $value
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	protected function validate_value( $value ) {
		if ( ! is_string( $value ) ) {
			throw new InvalidArgumentException();
		}
	}
}
