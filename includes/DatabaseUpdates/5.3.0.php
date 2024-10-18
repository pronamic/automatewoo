<?php

namespace AutomateWoo\DatabaseUpdates;

use AutomateWoo\Database_Tables;

defined( 'ABSPATH' ) || exit;

/**
 * Class Database_Update_5_3_0
 *
 * Alters AW's custom meta tables to allow `null` meta values since dbDelta() won't make this change.
 */
class Database_Update_5_3_0 extends AbstractDatabaseUpdate {

	/** @var string */
	protected $version = '5.3.0';

	/**
	 * @return string[]
	 */
	private function get_tables_to_update() {
		return [
			'log-meta',
			'queue-meta',
			'customer-meta',
			'guest-meta',
		];
	}

	/**
	 * Process a database update batch.
	 *
	 * @return bool Return true if update is complete, false if not yet complete.
	 */
	protected function process() {
		global $wpdb;

		foreach ( $this->get_tables_to_update() as $table_id ) {
			try {
				$table = Database_Tables::get( $table_id );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
				$wpdb->query( "ALTER TABLE {$table->get_name()} MODIFY meta_value longtext NULL" );
				++$this->items_processed;
			} catch ( \Exception $e ) {
				$this->log_processing_error( $e->getMessage() );
			}
		}

		return true;
	}

	/**
	 * @return bool|int
	 */
	public function get_items_to_process_count() {
		return count( $this->get_tables_to_update() );
	}
}

return new Database_Update_5_3_0();
