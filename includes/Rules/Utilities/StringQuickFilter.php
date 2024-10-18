<?php

namespace AutomateWoo\Rules\Utilities;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use AutomateWoo\RuleQuickFilters\Clauses\StringClause;
use InvalidArgumentException;

/**
 * Trait StringQuickFilter
 *
 * @since   5.0.0
 * @package AutomateWoo\Rules\Utilities
 */
trait StringQuickFilter {

	/**
	 * Get quick filter clause for a string compare rule.
	 *
	 * @param string $property     The property to filter against.
	 * @param string $compare_type Supports string-based compare types.
	 * @param mixed  $value
	 *
	 * @return ClauseInterface
	 *
	 * @throws InvalidArgumentException When there's an error getting the clause.
	 */
	protected function generate_string_quick_filter_clause( $property, $compare_type, $value ) {
		global $wpdb;

		$value = (string) $value;

		// Note: string queries should be case-insensitive as per \AutomateWoo\Rules\Rule::validate_string()
		// WP defaults to a case-insensitive database collation so we're OK
		switch ( $compare_type ) {
			case 'contains':
			case 'not_contains':
			case 'starts_with':
			case 'ends_with':
			case 'regex':
				$operator = strtoupper( $compare_type );
				break;
			case 'blank':
			case 'is':
				$operator = '=';
				break;
			case 'not_blank':
			case 'is_not':
				$operator = '!=';
				break;
			default:
				return new NoOpClause();
		}

		return new StringClause( $property, $operator, $value );
	}
}
