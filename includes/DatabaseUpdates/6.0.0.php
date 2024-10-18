<?php

namespace AutomateWoo\DatabaseUpdates;

defined( 'ABSPATH' ) || exit;

/**
 * Drops deprecated automatewoo_events table
 */
class Database_Update_6_0_0 extends AbstractDatabaseUpdate {

	/** @var string */
	protected $version = '6.0.0';


	/**
	 * Process a database update batch.
	 *
	 * @return bool Return true if update is complete, false if not yet complete.
	 */
	protected function process() {
		global $wpdb;
		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}automatewoo_events" );
			++$this->items_processed;
		} catch ( \Exception $e ) {
			$this->log_processing_error( $e->getMessage() );
			return false;
		}

		return true;
	}

	/**
	 * @return bool|int
	 */
	public function get_items_to_process_count() {
		return 1;
	}
}

return new Database_Update_6_0_0();
