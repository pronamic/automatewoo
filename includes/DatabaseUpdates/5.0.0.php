<?php
/**
 * Update to 5.0.0
 *
 * - set existing workflow types to "automatic"
 */

namespace AutomateWoo\DatabaseUpdates;

use AutomateWoo\Workflow_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Database_Update_5_0_0
 *
 * @package AutomateWoo\DatabaseUpdates
 */
class Database_Update_5_0_0 extends AbstractDatabaseUpdate {

	/** @var string */
	protected $version = '5.0.0';

	/**
	 * @return bool
	 */
	protected function process() {
		$limit = 5;
		$query = $this->get_workflows_query( $limit );

		$results = $query->get_results();

		if ( empty( $results ) ) {
			return true; // no more items to process, return complete
		}

		foreach ( $results as $workflow ) {
			$workflow->update_meta( 'type', 'automatic' );

			++$this->items_processed;
		}

		return false;
	}

	/**
	 * @return bool|int
	 */
	public function get_items_to_process_count() {
		// Set limit to minimum since it's irrelevant to total count
		$query = $this->get_workflows_query( 1 );

		// We need a call to this function to be able to count the results
		$query->get_results();

		return $query->get_found_rows();
	}

	/**
	 * @param int $limit
	 * @return Workflow_Query
	 */
	private function get_workflows_query( $limit ) {
		$query = new Workflow_Query();

		$query->set_no_found_rows( false );
		$query->set_limit( $limit );

		$query->args['meta_query'][] = [
			'key'     => 'type',
			'compare' => 'NOT EXISTS',
		];

		return $query;
	}

	/**
	 * Called immediately after database update is completed.
	 */
	protected function finish() {
		global $wpdb;
		$table = $wpdb->prefix . 'automatewoo_unsubscribes';

		// Avoid DB errors
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === null ) {
			return;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange

		// Only delete the table if it's empty
		$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		if ( is_numeric( $row_count ) && (int) $row_count === 0 ) {
			$wpdb->query( "DROP TABLE `{$table}`" );
		}

		// phpcs:enable

		parent::finish();
	}
}

return new Database_Update_5_0_0();
