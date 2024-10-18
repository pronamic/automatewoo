<?php

namespace AutomateWoo\RuleQuickFilters\Queries;

use AutomateWoo\Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractQuery.
 *
 * Quick filter results are a roughly filtered list of items that match a given workflow's rules.
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters\Queries
 */
abstract class AbstractQuery implements QueryInterface {

	/**
	 * Datastore type to query from.
	 *
	 * @var DatastoreTypeInterface
	 *
	 * @since 5.5.23
	 */
	protected $datastore;

	/**
	 * Quick filter clauses.
	 *
	 * Clauses are nested by rule group.
	 *
	 * @var array
	 */
	protected $clauses;

	/**
	 * Get the datastore type to use for queries.
	 *
	 * @return DatastoreTypeInterface
	 *
	 * @since 5.5.23
	 */
	abstract protected function get_datastore_type();

	/**
	 * AbstractQuery constructor.
	 *
	 * @param array $clauses An array containing arrays of clauses.
	 *                       See \AutomateWoo\RuleQuickFilters\ClauseGenerator::generate().
	 */
	public function __construct( $clauses ) {
		$this->clauses   = $clauses;
		$this->datastore = $this->get_datastore_type();
	}

	/**
	 * Get the number of rule groups for the rule data.
	 *
	 * @return int
	 */
	protected function get_rule_group_count() {
		return count( $this->clauses );
	}

	/**
	 * Get quick filter results by rule group number.
	 *
	 * We only retrieve one group at a time. Rule groups are numbered from 1.
	 *
	 * If the workflow has no rules this method can still be used with rule group set to 1.
	 * In this case it will return all results matching the data type.
	 *
	 * @param int    $rule_group
	 * @param int    $number
	 * @param int    $offset
	 * @param string $return Possible values objects, ids
	 *
	 * @return array
	 * @throws Exception When there is an error getting results.
	 */
	public function get_results_by_rule_group( $rule_group, $number = 10, $offset = 0, $return = 'objects' ) {
		$clauses = $this->get_clauses_by_rule_group( $rule_group );
		$results = $this->datastore->get_results_by_clauses( $clauses, $number, $offset );

		if ( $return === 'objects' ) {
			$results = array_filter( array_map( [ $this, 'get_result_object' ], $results ) );
		}

		return $results;
	}

	/**
	 * Get quick filter results count by rule group number.
	 *
	 * @param int $rule_group
	 *
	 * @return int
	 * @throws Exception When there is an error counting results.
	 */
	public function get_results_count_by_rule_group( $rule_group ) {
		$clauses = $this->get_clauses_by_rule_group( $rule_group );

		return $this->datastore->get_results_count_by_clauses( $clauses );
	}

	/**
	 * Get a count of all 'possible' results for every rule group.
	 *
	 * We can't get the exact value without comparing the results and removing duplicates, hence the word 'possible'.
	 *
	 * @return int
	 * @throws Exception When there is an error counting results.
	 */
	public function get_total_results_count() {
		return array_sum( $this->get_results_counts_for_each_rule_group() );
	}

	/**
	 * Get an array containing 'possible' results by rule group.
	 *
	 * We can't get the exact value without comparing the results and removing duplicates, hence the word 'possible'.
	 *
	 * @return array
	 * @throws Exception When there is an error getting results.
	 */
	public function get_results_counts_for_each_rule_group() {
		$counts           = [];
		$rule_group_count = $this->get_rule_group_count();

		if ( ! $rule_group_count ) {
			// There are no rules and therefore no clauses
			$counts[1] = $this->datastore->get_results_count_by_clauses( [] );
		}

		for ( $i = 1; $i <= $rule_group_count; $i++ ) {
			$counts[ $i ] = $this->get_results_count_by_rule_group( $i );
		}

		return $counts;
	}

	/**
	 * Get the clauses for a rule group.
	 *
	 * Returns false if rule group is invalid.
	 * Returns empty array if there are no filters for the rule group.
	 *
	 * IMPORTANT - If a rule group has no filters it may still have rules
	 * which means all possible results must be returned.
	 *
	 * @param int $rule_group_number
	 *
	 * @return array
	 * @throws Exception When the rule group number is invalid.
	 */
	protected function get_clauses_by_rule_group( $rule_group_number ) {
		if ( 0 === $this->get_rule_group_count() && 1 === $rule_group_number ) {
			// There are no rules so return empty array when getting group 1.
			return [];
		}

		if ( ! $rule_group_number || ! isset( $this->clauses[ $rule_group_number - 1 ] ) ) {
			throw new Exception( 'Rule group number is invalid.' );
		}

		return $this->clauses[ $rule_group_number - 1 ];
	}
}
