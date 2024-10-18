<?php
// phpcs:ignoreFile
/**
 * Update to 2.9.7
 * - remove unused table form guests table
 * - update queue data
 */

namespace AutomateWoo\DatabaseUpdates;

use AutomateWoo\Queue_Manager;
use AutomateWoo\Queue_Query;

if ( ! defined( 'ABSPATH' ) ) exit;

class Database_Update_2_9_7 extends AbstractDatabaseUpdate {

	protected $version = '2.9.7';


	/**
	 * @return bool
	 */
	protected function process() {

		global $wpdb;

		// clear unused column from guests
		if ( $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}automatewoo_guests LIKE 'ip2'" ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}automatewoo_guests DROP ip2" );
		}


		// migrate queue
		$query = new Queue_Query();
		$query->where( 'data_items', '', '!=' );
		$query->set_limit( 25 );
		$results = $query->get_results();

		if ( empty( $results ) ) {
			// no more items to process return complete...
			return true;
		}


		foreach ( $results as $queued_event ) {

			$data_items = $queued_event->data_items;

			foreach ( $data_items as $data_type_id => $data_item ) {
				$store_key = Queue_Manager::get_data_layer_storage_key( $data_type_id );
				$queued_event->update_meta( $store_key, $data_item );
			}

			$queued_event->data_items = '';
			$queued_event->save();
			$this->items_processed++;
		}

		return false;
	}

}

return new Database_Update_2_9_7();
