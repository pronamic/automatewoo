<?php

namespace AutomateWoo\Rules\Utilities;

use AutomateWoo\RuleQuickFilters\Clauses\ArrayClause;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use InvalidArgumentException;

/**
 * Trait ArrayQuickFilter
 *
 * @since   5.0.0
 * @package AutomateWoo\Rules\Utilities
 */
trait ArrayQuickFilter {

	/**
	 * Get quick filter clause for this rule.
	 *
	 * @param string $property     The property to filter against.
	 * @param string $compare_type Supports only simple array compare types 'is' or 'is_not'.
	 *                             'matches_any', 'matches_all', 'matches_none' can't be quick filtered.
	 * @param array  $value
	 *
	 * @return ClauseInterface
	 *
	 * @throws InvalidArgumentException When the value is invalid.
	 */
	protected function generate_array_quick_filter_clause( $property, $compare_type, $value ) {
		switch ( $compare_type ) {
			case 'is':
				$operator = 'IN';
				break;
			case 'is_not':
				$operator = 'NOT IN';
				break;
			default:
				return new NoOpClause();
		}

		return new ArrayClause( $property, $operator, (array) $value );
	}
}
