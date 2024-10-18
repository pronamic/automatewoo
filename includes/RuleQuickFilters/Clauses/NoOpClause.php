<?php

namespace AutomateWoo\RuleQuickFilters\Clauses;

defined( 'ABSPATH' ) || exit;

/**
 * Class NoOpClause
 *
 * Designates a clause that can't be quick filtered.
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters\Clauses
 */
class NoOpClause implements ClauseInterface {

	/**
	 * Get the clause property.
	 *
	 * @return null
	 */
	public function get_property() {
		return null;
	}

	/**
	 * Get the clause operator.
	 *
	 * @return null
	 */
	public function get_operator() {
		return null;
	}

	/**
	 * Get the clause value.
	 *
	 * @return null
	 */
	public function get_value() {
		return null;
	}
}
