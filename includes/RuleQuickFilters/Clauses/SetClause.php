<?php

namespace AutomateWoo\RuleQuickFilters\Clauses;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;


/**
 * Class SetClause
 *
 * Use to check whether a field is set or not set.
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters\Clauses
 */
class SetClause extends AbstractClause {

	/**
	 * SetClause constructor.
	 *
	 * @param string $property The property to filter against.
	 * @param string $operator 'SET' or 'NOT SET'
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	public function __construct( $property, $operator ) {
		parent::__construct( $property, $operator, null );
	}

	/**
	 * Validates the clause value.
	 *
	 * @param null $value
	 */
	protected function validate_value( $value ) {
		// The value will always be null.
	}
}
