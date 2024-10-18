<?php

namespace AutomateWoo\RuleQuickFilters;

use AutomateWoo\Exceptions\InvalidClass;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;
use AutomateWoo\RuleQuickFilters\Clauses\NoOpClause;
use AutomateWoo\Rules;
use AutomateWoo\Rules\Interfaces\NonPrimaryDataTypeQuickFilterable;
use AutomateWoo\Rules\Interfaces\QuickFilterable;

/**
 * Class ClauseGenerator
 *
 * Generates quick filter clauses based on rule data.
 *
 * @package AutomateWoo\RuleQuickFilters
 */
class ClauseGenerator {

	/**
	 * The primary data type to use.
	 *
	 * @var string
	 */
	protected $primary_data_type;

	/**
	 * The generated clauses.
	 *
	 * @var array
	 */
	protected $clauses = [];

	/**
	 * Generate quick filter clauses based on the given rule data.
	 *
	 * Clauses will be nested by rule group.
	 *
	 * @param array  $workflow_rules_data Rules data from a workflow.
	 * @param string $data_type           The primary data type to use.
	 *
	 * @throws \Exception When there is a problem generating clauses.
	 * @return array
	 */
	public function generate( $workflow_rules_data, $data_type ) {
		$this->primary_data_type = $data_type;

		if ( ! $workflow_rules_data ) {
			return [];
		}

		foreach ( $workflow_rules_data as $rule_group ) {
			$clause_group = [];

			foreach ( $rule_group as $single_rule ) {
				$this->get_rule_clause( $single_rule, $clause_group );
			}

			$this->clauses[] = $clause_group;
		}

		return $this->clauses;
	}

	/**
	 * Get the clause for a single rule.
	 *
	 * @param array $rule_data    The data for a single rule.
	 * @param array $clause_group The clauses for the rule group that this rule belongs to.
	 *
	 * @throws \Exception When there is a problem generating clauses.
	 */
	protected function get_rule_clause( $rule_data, &$clause_group ) {
		if ( ! is_array( $rule_data ) ) {
			return;
		}

		$rule_name    = isset( $rule_data['name'] ) ? $rule_data['name'] : false;
		$rule_compare = isset( $rule_data['compare'] ) ? $rule_data['compare'] : false;
		$rule_value   = isset( $rule_data['value'] ) ? $rule_data['value'] : false;

		$rule = Rules::get( $rule_name );

		// Get the quick filter clause for the primary data type
		if ( $rule instanceof QuickFilterable && $rule->data_item === $this->primary_data_type ) {
			$this->add_clauses_to_group(
				$rule->get_quick_filter_clause( $rule_compare, $rule_value ),
				$clause_group
			);
		}

		// Get the quick filter clause for any non-primary data types
		if ( $rule instanceof NonPrimaryDataTypeQuickFilterable && $rule->data_item !== $this->primary_data_type ) {
			$this->add_clauses_to_group(
				$rule->get_non_primary_quick_filter_clause( $this->primary_data_type, $rule_compare, $rule_value ),
				$clause_group
			);
		}
	}

	/**
	 * Add clauses to a clause group.
	 *
	 * @param ClauseInterface|ClauseInterface[] $clauses A single clause or an array of clauses.
	 * @param array                             $group   The clause group
	 *
	 * @throws InvalidClass If any clause is not valid.
	 */
	protected function add_clauses_to_group( $clauses, &$group ) {
		if ( $clauses instanceof NoOpClause ) {
			// Quick filtering is not possible for the rule.
			return;
		}

		$clauses = is_array( $clauses ) ? $clauses : [ $clauses ];

		foreach ( $clauses as $clause ) {
			if ( ! $clause instanceof ClauseInterface ) {
				throw InvalidClass::does_not_implement_interface( esc_html( get_class( $clause ) ), ClauseInterface::class );
			}
		}

		$group = array_merge( $group, $clauses );
	}
}
