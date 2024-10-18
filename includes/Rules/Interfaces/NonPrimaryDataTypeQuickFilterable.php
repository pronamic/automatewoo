<?php

namespace AutomateWoo\Rules\Interfaces;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use Exception;

/**
 * Interface NonPrimaryDataTypeQuickFilterableInterface
 *
 * @since 5.0.0
 * @package AutomateWoo\Rules
 */
interface NonPrimaryDataTypeQuickFilterable {

	/**
	 * Get any non-primary data type quick filter clauses for this rule.
	 *
	 * For example, this lets customer rules add filters to order and subscription queries.
	 *
	 * See also \AutomateWoo\Rules\QuickFilterableInterface::get_quick_filter_clause()
	 *
	 * @param string $data_type    The data type that is being filtered.
	 * @param string $compare_type The rule's compare type.
	 * @param mixed  $value        The rule's expected value.
	 *
	 * @throws Exception When there's an error getting the clause.
	 *
	 * @return ClauseInterface|ClauseInterface[]
	 */
	public function get_non_primary_quick_filter_clause( $data_type, $compare_type, $value );
}
