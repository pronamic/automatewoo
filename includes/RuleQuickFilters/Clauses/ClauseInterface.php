<?php

namespace AutomateWoo\RuleQuickFilters\Clauses;

/**
 * Class ClauseInterface
 *
 * @since 5.0.0
 * @package AutomateWoo\RuleQuickFilters\Clauses
 */
interface ClauseInterface {

	/**
	 * Get the clause property.
	 *
	 * @return string
	 */
	public function get_property();

	/**
	 * Get the clause operator.
	 *
	 * @return string
	 */
	public function get_operator();

	/**
	 * Get the clause value.
	 *
	 * @return mixed
	 */
	public function get_value();
}
