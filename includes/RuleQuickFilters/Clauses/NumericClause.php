<?php

namespace AutomateWoo\RuleQuickFilters\Clauses;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class NumericClause
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters\Clauses
 */
class NumericClause extends AbstractClause {

	/**
	 * NumericClause constructor.
	 *
	 * @param string    $property The property to filter against.
	 * @param string    $operator Should be a valid database WHERE operator.
	 * @param float|int $value    The quick filter clause value.
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	public function __construct( $property, $operator, $value ) {
		parent::__construct( $property, $operator, $value );
	}

	/**
	 * Validates the clause value.
	 *
	 * @param float|int $value
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	protected function validate_value( $value ) {
		if ( ! is_float( $value ) && ! is_integer( $value ) ) {
			throw new InvalidArgumentException();
		}
	}
}
