<?php

namespace AutomateWoo\RuleQuickFilters\Queries;

use AutomateWoo\Exception;

/**
 * Quick Filter Query Interface
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters\Queries
 */
interface QueryInterface {

	/**
	 * Get data type used for quick filtering.
	 *
	 * This is the manual workflow's primary data type.
	 *
	 * @return string
	 */
	public function get_data_type();

	/**
	 * Get filter result object from ID.
	 *
	 * @param int $id
	 *
	 * @return object
	 */
	public function get_result_object( $id );

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
	public function get_results_by_rule_group( $rule_group, $number = 10, $offset = 0, $return = 'objects' );

	/**
	 * Get quick filter results count by rule group number.
	 *
	 * @param int $rule_group
	 *
	 * @return int
	 * @throws Exception When there is an error counting results.
	 */
	public function get_results_count_by_rule_group( $rule_group );

	/**
	 * Get a count of all 'possible' results for every rule group.
	 *
	 * We can't get the exact value without comparing the results and removing duplicates, hence the word 'possible'.
	 *
	 * @return int
	 * @throws Exception When there is an error counting results.
	 */
	public function get_total_results_count();

	/**
	 * Get an array containing 'possible' results by rule group.
	 *
	 * We can't get the exact value without comparing the results and removing duplicates, hence the word 'possible'.
	 *
	 * @return array
	 * @throws Exception When there is an error counting results.
	 */
	public function get_results_counts_for_each_rule_group();
}
