<?php

namespace AutomateWoo\RuleQuickFilters\Queries;

use AutomateWoo\Exception;
use AutomateWoo\RuleQuickFilters\Clauses\ClauseInterface;

/**
 * Quick Filter Datastore Type Interface
 *
 * @since   5.5.23
 * @package AutomateWoo\RuleQuickFilters\Queries
 */
interface DatastoreTypeInterface {

	/**
	 * Get quick filter results by clauses.
	 *
	 * @param ClauseInterface[] $clauses A group of clauses.
	 * @param int               $number  The number of results to get.
	 * @param int               $offset  The query offset.
	 *
	 * @return array of IDs
	 * @throws Exception When there is an error getting results.
	 */
	public function get_results_by_clauses( $clauses, $number, $offset = 0 );

	/**
	 * Get quick filter results count by clauses.
	 *
	 * @param ClauseInterface[] $clauses A group of clauses.
	 *
	 * @return int
	 * @throws Exception When there is an error counting results.
	 */
	public function get_results_count_by_clauses( $clauses );
}
