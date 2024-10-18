<?php

namespace AutomateWoo\Rules\Interfaces;

use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use Exception;

/**
 * Interface QuickFilterable
 *
 * @since 5.0.0
 * @package AutomateWoo\Rules
 */
interface QuickFilterable {

	/**
	 * Get quick filter clause for this rule.
	 *
	 * Quick filter clauses are used to get reduce the number of possible matches for a workflow.
	 *
	 * For example, with the order status rule, quick filtering will reduce the total possible order matches
	 * by adding a 'post_status' arg to the database query.
	 *
	 * Quick filtering lets us get a rough idea of how many possible matches there are for a workflow based on its rules.
	 *
	 * Please note that not all rules will be able to support quick filtering.
	 *
	 * @param string $compare_type The rule's compare type.
	 * @param mixed  $value        The rule's expected value.
	 *
	 * @throws Exception When there's an error getting the clause.
	 *
	 * @return ClauseInterface|ClauseInterface[] A single clause or an array of clauses.
	 */
	public function get_quick_filter_clause( $compare_type, $value );
}
