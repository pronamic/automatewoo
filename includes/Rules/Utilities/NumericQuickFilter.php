<?php

namespace AutomateWoo\Rules\Utilities;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use AutomateWoo\RuleQuickFilters\Clauses\NumericClause;
use InvalidArgumentException;

/**
 * Trait NumericQuickFilter
 *
 * @since   5.0.0
 * @package AutomateWoo\Rules\Utilities
 */
trait NumericQuickFilter {

	/**
	 * Get quick filter clause for a numeric rule.
	 *
	 * @param string           $property     The property to filter against.
	 * @param string           $compare_type Supports float and integer compare types.
	 * @param float|int|string $value
	 *
	 * @return ClauseInterface
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	protected function generate_numeric_quick_filter_clause( $property, $compare_type, $value ) {
		// Cast numeric string to float
		if ( is_string( $value ) && is_numeric( $value ) ) {
			$value = (float) $value;
		}

		switch ( $compare_type ) {
			case 'is':
				$operator = '=';
				break;
			case 'is_not':
				$operator = '!=';
				break;
			case 'greater_than':
				$operator = '>';
				break;
			case 'less_than':
				$operator = '<';
				break;
			default:
				return new NoOpClause();
		}

		return new NumericClause( $property, $operator, $value );
	}
}
