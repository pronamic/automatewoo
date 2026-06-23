<?php

namespace AutomateWoo\RuleQuickFilters\Clauses;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrClause
 *
 * A compound clause that combines multiple sub-clauses with OR logic.
 * Used when a quick filter needs to match against multiple fields where
 * any one match is sufficient.
 *
 * @since 6.5.0
 * @package AutomateWoo\RuleQuickFilters\Clauses
 */
class OrClause implements ClauseInterface {

	/**
	 * The sub-clauses combined with OR logic.
	 *
	 * @var ClauseInterface[]
	 */
	protected $clauses;

	/**
	 * OrClause constructor.
	 *
	 * @param ClauseInterface[] $clauses Two or more clauses to combine with OR.
	 *
	 * @throws InvalidArgumentException When fewer than 2 clauses are provided.
	 */
	public function __construct( array $clauses ) {
		if ( count( $clauses ) < 2 ) {
			throw new InvalidArgumentException( 'OrClause requires at least 2 sub-clauses.' );
		}

		foreach ( $clauses as $clause ) {
			if ( ! $clause instanceof ClauseInterface ) {
				throw new InvalidArgumentException( 'All sub-clauses must implement ClauseInterface.' );
			}
		}

		$this->clauses = $clauses;
	}

	/**
	 * Get the sub-clauses.
	 *
	 * @return ClauseInterface[]
	 */
	public function get_clauses() {
		return $this->clauses;
	}

	/**
	 * Get the clause property.
	 *
	 * @return string
	 */
	public function get_property() {
		return 'or_group';
	}

	/**
	 * Get the clause operator.
	 *
	 * @return string
	 */
	public function get_operator() {
		return 'OR';
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
